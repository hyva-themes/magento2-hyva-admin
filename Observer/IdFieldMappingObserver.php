<?php declare(strict_types=1);

namespace Hyva\Admin\Observer;

use Hyva\Admin\Model\GridSourceType\RepositorySourceType\SearchCriteriaEventContainer;
use Hyva\Admin\Model\GridTypeReflection;
use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Event\Observer as Event;
use Magento\Framework\Event\ObserverInterface;

class IdFieldMappingObserver implements ObserverInterface
{
    /**
     * @var GridTypeReflection
     */
    private $typeReflection;

    public function __construct(GridTypeReflection $typeReflection)
    {
        $this->typeReflection = $typeReflection;
    }

    public function execute(Event $event)
    {
        /** @var SearchCriteriaEventContainer $searchCriteriaContainer */
        $searchCriteriaContainer = $event->getData('search_criteria_container');
        $type = $event->getData('record_type');

        $updatedSearchCriteria = $this->mapIdToEntityIdField($type, $searchCriteriaContainer->getSearchCriteria());

        $searchCriteriaContainer->replaceSearchCriteria($updatedSearchCriteria);
    }

    private function mapIdToEntityIdField(string $type, SearchCriteriaInterface $criteria): SearchCriteriaInterface
    {
        // preprocess $criteria to map ìd to entity_id when applicable
        $idFieldName = $this->typeReflection->getIdFieldName($type);
        if ($idFieldName && $idFieldName !== 'id') {
            foreach ($criteria->getFilterGroups() as $group) {
                foreach ($group->getFilters() as $filter) {
                    if ($filter->getField() === 'id') {
                        $filter->setField($idFieldName);
                    }
                }
            }

            if ($criteria->getSortOrders()) {
                foreach ($criteria->getSortOrders() as $sortOrder) {
                    if ($sortOrder->getField() == 'id') {
                        $sortOrder->setField($idFieldName);
                    }
                }
            }
        }
        return $criteria;
    }
}
