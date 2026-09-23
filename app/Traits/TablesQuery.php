<?php
namespace App\Traits;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

trait TablesQuery{
    public function getQuery(
        string $table,
        array $selectRow,
        array $join,
        $groupBy=null
    ): Builder
    {
        $query=DB::table($table);
        if (count($selectRow)>0){
            $query=$this->selectRow($query,$selectRow);
        }
        if (count($join)>0){
            $query=$this->join($query,$join);
        }
        if (request()->input('query') !== null){
            $query=$this->filterDataQuery($query);
        }
        if (isset($groupBy)){
            $query= $this->groupBy($query,$groupBy);
        }
        return $query;
    }
    protected function selectRow(Builder $query,array $selectRow): Builder
    {
        foreach ($selectRow as $row){
            $query=$query->selectRaw($row);
        }
        return $query;
    }
    protected function join(Builder $query,array $joins): Builder
    {
        foreach ($joins as $join){
            if (is_array($join) & count($join) == 5){
                if (strtolower($join[0]) == "default" ){
                    $query->join($join[1],$join[2],$join[3],$join[4]);
                }
                elseif (strtolower($join[0]) == "left" ){
                    $query->leftJoin($join[1],$join[2],$join[3],$join[4]);
                }
                elseif (strtolower($join[0]) == "right" ){
                    $query->rightJoin($join[1],$join[2],$join[3],$join[4]);
                }
                elseif (strtolower($join[0]) == "cross" ){
                    $query->crossJoin($join[1],$join[2],$join[3],$join[4]);
                }
            }
        }
        return $query;
    }
    protected function groupBy(Builder $query,string $groupBy): Builder
    {
        $groupByArr=explode(',',$groupBy);
        if (count($groupByArr) == 2){
            return $query->groupBy($groupByArr[0],$groupByArr[1]);
        }
        else
        {
            return $query->groupBy($groupByArr[0]);
        }

    }
    protected function filterDataQuery(Builder $query)
    {
        if (request()->input('query') != null || request()->input('query') != '')
        {
            $queries = explode(',', request()->input('query'));
            foreach ($queries as $que){
                $to_search = explode(':', $que);
                //            $attribute=$transformer::originalAttribute($query);
                if (count($to_search)==2)
                {
                    if (isset($to_search[0],$to_search[1])&&($to_search[1]!=null ||$to_search[1]!='')){
                        $query=$query->where($to_search[0],$to_search[1]);
                    }elseif (str_contains($to_search[0],'date')){
                        $query=$query->whereDate($to_search[0],$to_search[1]);
                    }
                }else{
                    if (isset($to_search[0],$to_search[1],$to_search[2])&&($to_search[2]!=null ||$to_search[2]!='')){
                        if ($to_search[1] == 'like'){
                            $query=$query->where($to_search[0],$to_search[1],'%'.$to_search[2].'%');
                        }
                        elseif (str_contains($to_search[0],'date')){
                            $query=$query->whereDate($to_search[0],$to_search[1],$to_search[2]);
                        }
                        else{
                            $query=$query->where($to_search[0],$to_search[1],$to_search[2]);
                        }
                    }
                }
            }
        }
        return $query;
    }

}
