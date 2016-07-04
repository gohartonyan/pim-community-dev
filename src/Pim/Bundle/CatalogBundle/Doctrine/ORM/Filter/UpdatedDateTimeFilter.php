<?php

namespace Pim\Bundle\CatalogBundle\Doctrine\ORM\Filter;

use Akeneo\Component\Batch\Job\BatchStatus;
use Akeneo\Component\Batch\Job\JobRepositoryInterface;
use Pim\Bundle\ImportExportBundle\Entity\Repository\JobInstanceRepository;
use Pim\Component\Catalog\Exception\InvalidArgumentException;
use Pim\Component\Catalog\Query\Filter\FieldFilterInterface;
use Pim\Component\Catalog\Query\Filter\Operators;

/**
 * Datetime filter for Updated field. It includes specific operators SINCE_LAST_EXPORT and SINCE_LAST_N_DAYS
 *
 * @author    Willy Mesnage <willy.mesnage@akeneo.com>
 * @copyright 2016 Akeneo SAS (http://www.akeneo.com)
 * @license   http://opensource.org/licenses/osl-3.0.php  Open Software License (OSL 3.0)
 */
class UpdatedDateTimeFilter extends AbstractFieldFilter implements FieldFilterInterface
{
    const DATETIME_FORMAT = 'Y-m-d H:i:s';

    /** @var JobRepositoryInterface */
    protected $jobRepository;

    /** @var JobInstanceRepository */
    protected $jobInstanceRepository;

    /**
     * @param JobInstanceRepository  $jobInstanceRepository
     * @param JobRepositoryInterface $jobRepository
     * @param array                  $supportedFields
     * @param array                  $supportedOperators
     */
    public function __construct(
        JobInstanceRepository $jobInstanceRepository,
        JobRepositoryInterface $jobRepository,
        array $supportedFields = [],
        array $supportedOperators = []
    ) {
        $this->supportedFields       = $supportedFields;
        $this->supportedOperators    = $supportedOperators;
        $this->jobRepository         = $jobRepository;
        $this->jobInstanceRepository = $jobInstanceRepository;
    }

    /**
     * {@inheritdoc}
     */
    public function addFieldFilter($field, $operator, $value, $locale = null, $scope = null, $options = [])
    {
        $this->checkValue($field, $operator, $value);

        if (Operators::SINCE_LAST_EXPORT === $operator) {
            $this->addUpdatedSinceLastExport($field, $value);
        } elseif (Operators::SINCE_LAST_N_DAYS === $operator) {
            $this->addSinceLastNDays($field, $value);
        }

        return $this;
    }

    /**
     * Add a filter for products updated since N ($value) days to the query builder
     *
     * @param string $field
     * @param string $value
     */
    protected function addSinceLastNDays($field, $value)
    {
        $fromDate = new \DateTime(sprintf('%s days ago', $value), new \DateTimeZone('UTC'));
        $updatedField = current($this->qb->getRootAliases()) . '.' . $field;

        $this->applyGreaterThanFilter($updatedField, $fromDate->format(static::DATETIME_FORMAT));
    }

    /**
     * Add a filter for products updated since the last export to the query builder
     *
     * @param string $field
     * @param string $value
     */
    protected function addUpdatedSinceLastExport($field, $value)
    {
        $jobInstance = $this->jobInstanceRepository->findOneBy(['code' => $value]);
        if (null === $jobInstance) {
            return;
        }

        $lastCompletedJobExecution = $this->jobRepository->getLastJobExecution($jobInstance, BatchStatus::COMPLETED);
        $lastJobStartTime = $lastCompletedJobExecution->getStartTime();
        $updatedField = current($this->qb->getRootAliases()) . '.' . $field;

        $this->applyGreaterThanFilter($updatedField, $lastJobStartTime->format(static::DATETIME_FORMAT));
    }

    /**
     * @param string $field
     * @param string $datetime
     */
    protected function applyGreaterThanFilter($field, $datetime)
    {
        $this->qb->andWhere(
            $this->qb->expr()->gt(
                $field,
                $this->qb->expr()->literal($datetime)
            )
        );
    }

    /**
     * Check if value is valid
     *
     * @param string $field
     * @param string $operator
     * @param mixed  $value
     */
    protected function checkValue($field, $operator, $value)
    {
        if ($operator === Operators::SINCE_LAST_EXPORT && !is_string($value)) {
            throw InvalidArgumentException::stringExpected($field, 'filter', 'updated', gettype($value));
        }

        if ($operator === Operators::SINCE_LAST_N_DAYS && !is_numeric($value)) {
            throw InvalidArgumentException::numericExpected($field, 'filter', 'updated', gettype($value));
        }
    }
}