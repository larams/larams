<?php

namespace Larams\Cms;

/**
 * Class StructureItem
 * @package Larams\Cms
 *
 * @method static StructureItem forLang($currentLanguage, $isActive = 1, $inTree = 1)
 * @method static StructureItem byTypeName($typeName)
 * @method static StructureItem byParentTypeName($typeName)
 * @method static StructureItem byParentId($itemId)
 * @method static StructureItem whereData($key, $operator = '=', $value = null)
 * @method static StructureItem byId($itemId)
 * @method static StructureItem orderByData($column, $direction = 'asc')
 */

class StructureItem extends \Eloquent
{

    public static $currLang;

    public static $currSite;

    public static $currItem;

    public static $currPath;

    protected $table = 'structure_items';

    protected $fillable = ['id', 'parent_id', 'user_id', 'name', 'date', 'level', 'type_id', 'left', 'right', 'active', 'tree', 'sort', 'uri', 'custom_uri', 'search'];

    protected $appends = ['data'];

    protected $hidden = ['content'];

    public function type()
    {
        return $this->belongsTo('Larams\Cms\StructureType', 'type_id');
    }

    public function childs()
    {
        return $this->hasMany('Larams\Cms\StructureItem', 'parent_id');
    }

    public function parent()
    {
        return $this->belongsTo('Larams\Cms\StructureItem', 'parent_id');
    }

    public function user()
    {
        return $this->belongsTo('Larams\Cms\User', 'user_id');
    }

    public function content()
    {
        return $this->hasMany('Larams\Cms\StructureData', 'item_id');
    }

    public function scopeChildsOf($query, $itemId)
    {
        $item = $this->find($itemId);

        return $query->where('left', '>', $item->left)->where('right', '<', $item->right);
    }

    public function scopePath($query, $left, $right, $includeSelf = true)
    {

        if ($includeSelf) {
            return $query->where('left', '<=', $left)->where('right', '>=', $right);
        }

        return $query->where('left', '<', $left)->where('right', '>', $right);
    }

    public function getPathElements( $left, $right )
    {
        return $this->path( $left, $right )->where('active', 1)->take( PHP_INT_MAX )->orderBy('left')->offset( 1 )->get()->toArray();
    }

    public function getFullUriAttribute()
    {
        $uri = $this->getPathElements( $this->left, $this->right );

        return trim(implode('/', array_map(function ($item) {
            return !empty($item['custom_uri']) ? $item['uri'] : Utils::toAscii($item['name']);
        }, $uri)), '/');

    }

    public function delete()
    {

        $this->where('left', '>', $this->left)->where('right', '<', $this->right)->delete();

        $delta = $this->right - $this->left + 1;

        $this->where('left', '>', $this->left)->decrement('left', $delta);
        $this->where('right', '>', $this->right)->decrement('right', $delta);

        return parent::delete();
    }

    public function getDataAttribute()
    {

        if (!isset($this->content)) {
            $this->content = $this->content()->get();
        }

        return (object)$this->content->lists('data', 'name')->toArray();
    }

    /**
     * @param \Eloquent $query
     * @param int $tree
     * @return mixed
     */
    public function scopeForLang($query, $currLang, $isActive = 1, $inTree = 1)
    {
        return $query->where('structure_items.active', $isActive)->where('structure_items.tree', $inTree)->where('structure_items.left', '>', $currLang->left)->where('structure_items.right', '<', $currLang->right);
    }

    public function scopeByParentTypeName($query, $typeName)
    {
        return $query
            ->leftJoin('structure_items AS SI2', 'structure_items.parent_id', '=', 'SI2.id')
            ->leftJoin('structure_types', 'SI2.type_id', '=', 'structure_types.id')
            ->where('structure_types.name', $typeName)
            ->select('structure_items.*', 'structure_types.name AS parent_type_name');
    }

    /**
     * @param \Eloquent $query
     * @param $typeName
     * @return mixed
     */
    public function scopeByTypeName($query, $typeName)
    {
        return $query->leftJoin('structure_types', 'structure_items.type_id', '=', 'structure_types.id')->where('structure_types.name', $typeName)->select('structure_items.*', 'structure_types.name AS type_name');
    }

    /**
     * @param \Eloquent $query
     * @param $column
     * @param string $direction
     * @return mixed
     */
    public function scopeOrderByData($query, $column, $direction = 'asc')
    {
        $alias = uniqid();

        return $query->leftJoin('structure_data AS ' . $alias, function ($join) use ($column, $alias) {
            $join->on('structure_items.id', '=', $alias . '.item_id');
            $join->on($alias . '.name', '=', \DB::raw("'{$column}'"));
        })->orderBy($alias . '.data', $direction)
            ->select(['structure_items.*']);
    }

    /**
     * @param \Eloquent $query
     * @param $key
     * @param null $value
     * @return mixed
     */
    public function scopeWhereData($query, $key, $operator = '=', $value = null)
    {

        $alias = uniqid();

        if (is_null($value)) {
            $value = $operator;
            $operator = '=';
        }

        return $query->leftJoin('structure_data AS ' . $alias, 'structure_items.id', '=', $alias . '.item_id')
            ->where($alias . '.name', $key)
            ->where($alias . '.data', $operator, $value)
            ->select(['structure_items.*']);
    }

    /**
     * @param \Eloquent $query
     * @param $parentId
     * @return mixed
     */
    public function scopeByParentId($query, $parentId)
    {
        return $query->where('parent_id', $parentId);
    }

    public function scopeById($query, $id)
    {
        return $query->where('id', $id);
    }

    public function scopeByKeyword($query, $keyword)
    {
        return $query->whereRaw('MATCH( search ) AGAINST ( ? IN BOOLEAN MODE )', [$keyword]);
    }

    public function updateChildUris($item)
    {

        $childs = $this->where('left', '>', $item->left)->where('right', '<', $item->right)->get();

        foreach ($childs as $child) {
            if (!empty( $child->custom_uri )) {
                continue;
            }

            $child->uri = $child->full_uri;
            $child->save();
        }

    }

    public function addItem($data, StructureItem $parent)
    {

        $this->where('left', '>', $parent->right)->increment('left', 2);
        $this->where('right', '>', $parent->right - 1)->increment('right', 2);

        if (!isset($data['parent_id'])) {
            $data['parent_id'] = $parent->id;
        }

        if (!isset($data['left'])) {
            $data['left'] = $parent->right;
        }

        if (!isset($data['right'])) {
            $data['right'] = $parent->right + 1;
        }

        if (!isset($data['level'])) {
            $data['level'] = $parent->level + 1;
        }

        return $this->create($data);
    }

    public function rebuildTree($parentId = null, $left = 0)
    {
        $left = $left + 1;

        $items = $this->where('parent_id', $parentId)->orderBy('left')->orderBy('created_at')->get();
        foreach ($items as $item) {
            $item->left = $left;
            $left = $this->rebuildTree($item->id, $left);
            $item->right = $left;
            $item->save();

            $left++;
        }

        return $left;
    }

    public function getOrSet($property, $value = null)
    {
        if (!is_null($value)) {
            static::$$property = $value;
        }

        return static::$$property;
    }

    /**
     * @param null $value
     * @return StructureItem
     */
    public function currLang($value = null)
    {
        return $this->getOrSet('currLang', $value);
    }

    /**
     * @param null $value
     * @return StructureItem
     */
    public function currSite($value = null)
    {
        return $this->getOrSet('currSite', $value);
    }

    /**
     * @param null $value
     * @return StructureItem
     */
    public function currItem($value = null)
    {
        return $this->getOrSet('currItem', $value);
    }

    /**
     * @param null $value
     * @return StructureItem
     */
    public function currPath($value = null)
    {
        return $this->getOrSet('currPath', $value);
    }

    public function move($newParentId, $newPosition)
    {

        $parent = $this->find($newParentId);
        $elementWidth = $this->right - $this->left + 1;

        $newLeft = $parent->left + 1;
        $elementInPosition = $this->byParentId($parent->id)->offset($newPosition - 1)->first();
        if (!empty($elementInPosition)) {
            $newLeft = $elementInPosition->right + 1;
        }

        $distance = $newLeft - $this->left;
        $tmpLeft = $this->left;

        if ($distance < 0) {
            $distance -= $elementWidth;
            $tmpLeft += $elementWidth;
            $symbol = '';
        } else {
            $symbol = '+';
        }

        // Create new space for subtree
        $this->where('left', '>=', $newLeft)->update(['left' => \DB::raw('`left` + ' . $elementWidth)]);
        $this->where('right', '>=', $newLeft)->update(['right' => \DB::raw('`right` + ' . $elementWidth)]);

        // Move subtree into new space
        $this->where('left', '>=', $tmpLeft)->where('right', '<', $tmpLeft + $elementWidth)->update(['left' => \DB::raw('`left` ' . $symbol . $distance), 'right' => \DB::raw('`right` ' . $symbol . $distance)]);

        // Remove old space vacated by subtree
        $this->where('left', '>', $this->right)->update(['left' => \DB::raw('`left` -' . $elementWidth)]);
        $this->where('right', '>', $this->right)->update(['right' => \DB::raw('`right` -' . $elementWidth)]);


        $this->parent_id = $parent->id;
        $this->level = $parent->level + 1;
        $this->save();

    }
}
