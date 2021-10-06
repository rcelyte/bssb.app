<?php

namespace app\Data;

use app\Data\Filters\BaseFilter;
use app\HTTP\Request;
use app\Models\HostedGame;
use app\Models\Joins\HostedGameLevelRecord;
use SoftwarePunt\Instarecord\Database\ModelQuery;

final class GameQuery
{
    /**
     * @var BaseFilter[]
     */
    private array $filters;
    /**
     * @var string[]
     */
    private array $filterValues;

    public bool $hideStaleGames = true;
    public bool $hideEndedGames = true;

    public function __construct()
    {
        $this->filters = [];
        $this->filterValues = [];
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function execute(): GameQueryResult
    {
        $result = new GameQueryResult();
        $result->filters = $this->filters;
        $result->filterValues = $this->filterValues;
        $result->filterOptions = [];

        // Get options for all filters with a sub query
        foreach ($this->filters as $filterId => $filter) {
            $filterSubQuery = $this->buildFilteredQuery(false, $filterId);
            $result->filterOptions[$filterId] = $filter->queryOptions($filterSubQuery);
        }

        // Execute main query for games, with all filters applied
        $query = $this->buildFilteredQuery();
        $result->games = $query->queryAllModels();

        return $result;
    }

    // -----------------------------------------------------------------------------------------------------------------

    public function addFilter(BaseFilter $filter): void
    {
        $this->filters[$filter->getId()] = $filter;
    }

    public function applyFiltersFromRequest(Request $request): void
    {
        $this->filterValues = [];

        foreach ($request->queryParams as $key => $value) {
            $filter = $this->filters[$key] ?? null;

            if (!$filter)
                continue;

            $this->filterValues[$key] = (string)$value;
        }
    }

    // -----------------------------------------------------------------------------------------------------------------

    private function buildBaseQuery(bool $joinLevelData = true): ModelQuery
    {
        $query = HostedGameLevelRecord::query()
            ->from("hosted_games")
            ->orderBy("player_count >= player_limit ASC, player_count > 1 ASC, player_limit DESC, hosted_games.id DESC");

        if ($joinLevelData) {
            $query->select("hosted_games.*, lr.beatsaver_id, lr.cover_url, lr.name AS level_name")
                ->leftJoin("level_records lr ON (lr.level_id = hosted_games.level_id)");
        }

        if ($this->hideStaleGames) {
            $query->andWhere("last_update >= ?", HostedGame::getStaleGameCutoff());
        }

        if ($this->hideEndedGames) {
            $query->andWhere("ended_at IS NULL");
        }

        return $query;
    }

    private function buildFilteredQuery(bool $joinLevelData = true, ?string $skipFilterId = null): ModelQuery
    {
        $query = $this->buildBaseQuery($joinLevelData);

        foreach ($this->filters as $filterId => $filter) {
            if ($skipFilterId && $filterId === $skipFilterId)
                continue;

            $filterValue = $this->filterValues[$filterId] ?? null;
            $filter->applyFilter($query, $filterValue);
        }

        return $query;
    }
}