<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use App\Http\Requests\UserRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class Usercontroller extends Controller
{
  /**
   * Display a listing of the resource.
   */
  public function index(Request $request)
  {
    //?search=3210&sort=name&order=desc&page=3&perPage=5

    $query = User::query();

    // Total users count (before filtering)
    $total = $query->count();

    // Filter query
    $filteredQuery = $query
      ->when($request->has('search'), function ($query) use ($request) {
        $query->where('name', 'LIKE', "%{$request->search}%")
          ->orWhere('email', 'LIKE', "%{$request->search}%")
          ->orWhere('contact', 'LIKE', "%{$request->search}%");
      })
      ->when($request->has('name'), function ($query) use ($request) {
        $query->where('name', 'LIKE', "%{$request->name}%");
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
      $total,
      $filteredQuery,
      $request->perPage ?? 10
    );
  }

  /**
   * Store a newly created resource in storage.
   */
  public function store(UserRequest $request)
  {
    return $this->update($request); // Delegate to update method
  }

  /**
   * Display the specified resource.
   */
  public function show($id)
  {
    $record = User::select('id', 'name', 'email')->where('id',$id)->first();

    if (!$record) {
      return response()->json(['message' => 'No record found.'], 404); // Handle not found
    }

    return response()->json($record);
  }

  /**
   * Update the specified resource in storage.
   */
  public function update(UserRequest $request, $id = null)
  {
    // $rule = [
    //     'name' => [
    //         'required',
    //         'string',
    //         'max:255',
    //     ],
    //     'email' => [
    //         'required',
    //         'string',
    //         'lowercase',
    //         'email',
    //         'max:255',
    //         $id ? Rule::unique('users', 'email')->ignore($id) : Rule::unique('users', 'email'),
    //     ],
    //     'password' => [
    //         $id  ? 'sometime' : 'required',
    //         'string',
    //         'confirmed',
    //         Rules\Password::defaults()
    //     ],
    // ];

    // // $request->validate($rule);

    // $validator = Validator::make($request->all(), $rule);

    // if ($validator->fails()) {
    //     $this->validationError($validator->messages());
    // }

    // // Retrieve the validated input...
    // $validatedData = $validator->validated();


    return DB::transaction(function () use ($request, $id) {
      // Get only validated data
      $validatedData = $request->validated();

      // Find or create record
      $record = $id ? User::find($id) : new User;

      if (!$record) {
        return response()->json(['message' => 'No record found.'], 404);
      }

      // Update the record
      $oldPassword = $record->password ?? Hash::make('password');
      $record->fill([
        'name' => $validatedData['name'],
        'email' => $validatedData['email'],
        'password' => (isset($validatedData['password']) && $validatedData['password'])
          ? Hash::make($validatedData['password'])
          : $oldPassword
      ])->save();

      return response()->json($record, $id ? 200 : 201); // Return appropriate status codes
    }, 3); // Retry up to 3 times in case of a deadlock
  }

  /**
   * Remove the specified resource from storage.
   */
  public function destroy($id)
  {
    // Find record
    $record = User::where('id',$id)->first();

    if (!$record) {
      return response()->json(['message' => 'No Record found.'], 404); // Handle not found
    }
    $record->delete();
    return response()->noContent()->setStatusCode(204);
  }
}
