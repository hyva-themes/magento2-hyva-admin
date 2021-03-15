<?php declare(strict_types=1);

namespace Hyva\Admin\Model\TypeReflection;

use Magento\Framework\App\ResourceConnection;
use Magento\Framework\DB\Adapter\AdapterInterface;

use function array_map as map;
use function array_merge as merge;
use function array_reduce as reduce;

class EavAttributeGroups
{
    /**
     * @var AdapterInterface
     */
    private $db;

    public function __construct(ResourceConnection $resourceConnection)
    {
        $this->db = $resourceConnection->getConnection();
    }

    public function getAttributeToGroupCodeMapForSet(int $attributeSetId): array
    {
        $attributeToGroupMap = $this->buildAttributeToGroupConfigMapForSet($attributeSetId);
        return map([$this, 'getGroupCode'], $attributeToGroupMap);
    }

    public function getGroupsForAttributeSet(int $attributeSetId): array
    {
        $attributeToGroupMap = $this->buildAttributeToGroupConfigMapForSet($attributeSetId);
        return reduce($attributeToGroupMap, function (array $map, array $group): array {
            $groupCode           = $this->getGroupCode($group);
            $groupAttributeCodes = $map[$groupCode]['attributes'] ?? [];
            $map[$groupCode]     = [
                'id'         => $groupCode,
                'label'      => $group['group_name'],
                'sortOrder'  => $group['group_sort_order'],
                'attributes' => merge($groupAttributeCodes, [$group['attribute_code']]),
            ];
            return $map;
        }, []);
    }

    private function getGroupCode(array $group): string
    {
        return $group['group_code'] ?? str_replace(' ', '_', strtolower($group['group_name']));
    }

    private function buildAttributeToGroupConfigMapForSet(int $attributeSetId): array
    {
        $entityAttributeTable   = $this->db->getTableName('eav_entity_attribute');
        $eavAttributeTable      = $this->db->getTableName('eav_attribute');
        $eavAttributeGroupTable = $this->db->getTableName('eav_attribute_group');

        $select = $this->db->select()
                           ->from(
                               ['ea' => $eavAttributeTable],
                               ['ea.attribute_code']
                           )
                           ->joinLeft(
                               ['eea' => $entityAttributeTable],
                               'ea.attribute_id = eea.attribute_id',
                               ['attribute_sort_order' => 'eea.sort_order'])
                           ->joinInner(
                               ['eag' => $eavAttributeGroupTable],
                               'eea.attribute_group_id = eag.attribute_group_id',
                               [
                                   'group_name'       => 'eag.attribute_group_name',
                                   'group_code'       => 'eag.attribute_group_code',
                                   'group_sort_order' => 'eag.sort_order',
                               ])
                           ->where('eea.attribute_set_id=?', $attributeSetId)
                           ->order('group_sort_order')
                           ->order('eea.attribute_group_id')
                           ->order('attribute_sort_order');

        return $this->db->fetchAssoc($select);
    }
}
