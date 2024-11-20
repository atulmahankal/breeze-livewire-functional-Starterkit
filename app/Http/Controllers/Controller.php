<?php

namespace App\Http\Controllers;

abstract class Controller
{
    public function customePagination($total, $filteredQuery, $perPage = 10)
    {
      // Paginate the filtered results
      $paginatedResults = $filteredQuery->paginate($perPage ?? 10);

      return response()->json(array(
        'current_page' => $paginatedResults->currentPage(),
        'from' => $paginatedResults->firstItem(),
        'to' => $paginatedResults->lastItem(),
        'last_page' => $paginatedResults->lastPage(),
        'per_page' => $paginatedResults->perPage(),
        'data' => $paginatedResults->items(),
        'filtered_total' => $paginatedResults->total(),
        'total' => $total
      ));
    }
}
