<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;

class Usercontroller extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    //?search=3210&sort=name&order=desc&perPage=5&page=3

    // Filter query
    $filteredQuery = User::query()
      ->when($request->has('search'), function ($query) use ($request) {
        $query->where('name', 'like', "%{$request->search}%")
          ->orWhere('email', 'LIKE', "%{$request->search}%")
          ->orWhere('contact', 'LIKE', "%{$request->search}%");
      })
      ->when($request->has('name'), function ($query) use ($request) {
        $query->where('name', 'like', "%{$request->name}%");
      })
      ->when($request->has('email'), function ($query) use ($request) {
        $query->where('email', 'LIKE', "%{$request->email}%");
      })
      ->when($request->has('contact'), function ($query) use ($request) {
        $query->where('contact', 'LIKE', "%{$request->contact}%");
      })
      ->when($request->has('sort') && $request->has('order'), function ($query) use ($request) {
        $sortColumn = $request->sort;
        $sortOrder = $request->order;

        // Validate the 'order' value to ensure it's either 'asc' or 'desc'
        if (!in_array(strtolower($sortOrder), ['asc', 'desc'])) {
          $sortOrder = 'asc';
        }
        $query->orderBy($sortColumn, $sortOrder);
      })
      ->select('id', 'name', 'email');

    return $this->customePagination(
      User::count(),
      $filteredQuery,
      $request->perPage ?? 10
    );
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(Request $request)
  {
    //
  }

  /**
   * Display the specified resource.
   */
  public function show($id)
  {
    $record = User::select('id', 'name', 'email')->where('id',$id)->first();

    if (!$record) {
      return response()->json(['message' => 'No Record found.'], 404); // Handle not found
    }

    return response()->json($record);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(Request $request, string $id)
  {
    //
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy(string $id)
  {
    //
  }
}
