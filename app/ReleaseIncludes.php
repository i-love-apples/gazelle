<?php

namespace Gazelle;

class ReleaseIncludes extends Base {
    protected const CACHE_KEY = 'release_includes';

    /** @var array */
    protected $list;

    public function __construct() {
        if (($this->list = self::$cache->get_value(self::CACHE_KEY)) === false) {
            $qid = self::$db->get_query_id();
            self::$db->prepared_query("
                SELECT ID, Name FROM release_includes ORDER BY ID
            ");
            $this->list = self::$db->to_pair('ID', 'Name');
            self::$db->set_query_id($qid);
            self::$cache->cache_value(self::CACHE_KEY, $this->list, 86400 * 30);
        }
    }

    public function list(): array {
        return $this->list;
    }

    public function findIdByName(string $name) {
        return array_search($name, $this->list) ?: array_search('Unknown', $this->list);
    }

    public function findNameById(int $id) {
        return $this->list[$id] ?? null;
    }
}
