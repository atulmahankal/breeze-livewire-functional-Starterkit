<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use App\Http\Requests\UserRequest;
use function Livewire\Volt\{layout, title, state, computed, on};

layout('layouts.app');
title('Users');

state([
  'search' => '',
  'sort' => '',
  'order' => 'asc',
  'perPage' => '10',
  'page' => '1',
])->url(history: true);

state([
  'user' => array(),
  'users' => function () {
    try {
      $request = new Request([
        'search' => $this->search ?? null,
        'sort' => $this->sort ?? null,
        'order' => $this->order ?? 'asc',
        'perPage' => $this->perPage ?? 10,
        'page' => $this->page ?? 1,
      ]);
      $response = app(App\Http\Controllers\UserController::class)->index($request);

      $data = $response->getData(true);
      $status = $response->getStatusCode();

      return $data;
    } catch (\Exception $e) {
      // dd($e->getMessage(), $e->getTrace()); // Inspect the error message and stack trace
      return array(
        data => []
      );
    }
  }
]);

$resetUser = function (){
  $this->user = array();
  // $this->user = array(
  //   'name' => null,
  //   'email' => null,
  //   'password' => null,
  //   'password_confirmation' => null
  // );
};

$createUser = function () {
  $this->resetUser();
  $this->dispatch('open-modal', 'user-modal');
};

$updateUser = function ($userId) {
  try {
    $response = app(App\Http\Controllers\UserController::class)->show($userId);
    $result = $response->getData(true);
    $status = $response->getStatusCode();

    if($status == 200) {
      $this->user = $result;
      $this->dispatch('open-modal', 'user-modal');
    } else {
      Session::flash('status', __($result['message']));
      // throw new \ErrorException($result['message']);
    }
  } catch (\Exception $e) {
    // dd($e->getMessage(), $e->getTrace()); // Inspect the error message and stack trace
    Session::flash('status', __($e->getMessage()));
  }
};

$saveUser = function () {
//   try {
    $userId = $this->user['id'] ?? null;

    // Make the POST request
    if(isset($this->user['id'])){
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-CSRF-TOKEN' => session()->token(),
        ])->put(url(`api/users/${$this->user['id']}`), $this->user);
    } else {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-CSRF-TOKEN' => session()->token(),
        ])->post(url('api/users'), $this->user);
        dd('working with sanctum');
    }

    $responseBody = json_decode($response->body(), true);

    if($response->failed()){
        $this->dispatch('show-error', $responseBody['message']);
        if(isset($responseBody['errors'])){
            foreach ($responseBody['errors'] as $key => $messages) {
                $this->addError($key, implode(', ', $messages));
            }
        }
    }

    if($response->successful()){
        $this->dispatch('close-modal', 'user-modal');
        $this->dispatch('show-message', $responseBody['message'] ?? 'Record saved successfully!');
    }

//   } catch (\Exception $e) {
//     // Get the exception type
//     // dd("Exception Type: ".get_class($e), $e->getMessage());

//     $this->dispatch('show-error', $e->getMessage());
//     // Session::flash('status', __($e->getMessage()));
//   }
};

$deleteUser = function ($userId) {
  try {
    $response = app(App\Http\Controllers\UserController::class)->destroy($userId);

    $status = $response->getStatusCode();
    if($status == 204) {
      $this->redirectIntended(request()->header('Referer'), navigate: true);
    } else {
      dd('failed:',$response);
      Session::flash('status', __($response));
    }
  } catch (\Exception $e) {
    // Get the exception type
    $exceptionType = get_class($e);
    dd('error:', $e);

    // dd($e->getMessage(), $e->getTrace()); // Inspect the error message and stack trace
    // $this->dispatch('close-modal', 'user-modal');
    Session::flash('status', __($e->getMessage()));
  }
}
?>
{{-- Meta Section --}}
@section('meta')
    <meta name="description" content="CRUD operation for Users">
    <meta name="keywords" content="Laravel, Blade, Template">
@endsection

{{-- Additional Scripts --}}
@push('scripts')
<script>
    document.addEventListener('show-error', (event) => {
        alert(`${event.detail}`);
    });
    document.addEventListener('show-message', (event) => {
        alert(`${event.detail}`);
    });
</script>
@endpush

<div>
  <div class="flex justify-between px-6 py-2 bg-white">
    <div>User List</div>
    <div>
      <x-primary-button x-data="" wire:click="createUser">
        {{ __('Create') }}
      </x-primary-button>

      <x-modal name="user-modal" :show="$errors->isNotEmpty()" focusable>
        <form wire:submit="saveUser" class="p-6">

          <h2 class="text-lg font-medium text-gray-900">
            {{ !isset($user['id']) ? 'Create' : 'Update' }} User
          </h2>

          <div class="mt-6">
            <div class="mb-4">
              <x-input-label for="name" :value="__('Name')" />
              <x-text-input wire:model="user.name" id="name" placeholder="{{ __('Name') }}" class="block w-full mt-1"
                 />
              <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="mb-4">
              <x-input-label for="email" :value="__('E-mail')" />
              <x-text-input wire:model="user.email" id="email" placeholder="{{ __('E-mail') }}" type="email"
                class="block w-full mt-1" />
              <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>

            @if(!isset($user['id']))
            <div class="mb-4">
              <x-input-label for="password" :value="__('Password')" />
              <x-text-input wire:model="user.password" id="password" placeholder="{{ __('Password') }}" type="password"
                class="block w-full mt-1" />
              <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            <div class="mb-4">
              <x-input-label for="password_confirmation" :value="__('Confirm Password')" />
              <x-text-input wire:model="user.password_confirmation" id="password_confirmation" placeholder="{{ __('Confirm Password') }}" type="password"
                class="block w-full mt-1" />
              <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
            </div>
            @endif
          </div>

          <div class="flex justify-end mt-6">
            <x-secondary-button x-on:click="$dispatch('close')">
              {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ms-3">
              {{ __('Save') }}
            </x-primary-button>
          </div>
        </form>
      </x-modal>
    </div>
  </div>

  <!-- Session Status -->
  @if(session('status'))
  <div class="w-full p-2 mb-4 overflow-hidden transition duration-300 ease-out">
    <div class="p-4 bg-white rounded shadow">
      <x-auth-session-status :status="session('status')" />
    </div>
  </div>
  @endif

  <div class="relative px-2 py-2 m-3 overflow-x-auto bg-white shadow-md sm:rounded-lg">
    <table class="w-full text-sm text-left text-gray-500 table-fixed rtl:text-right dark:text-gray-400">
      <thead>
        <tr>
          <th class="text-center border">#</th>
          <th class="p-2 text-center border">Name</th>
          <th class="p-2 text-center border">Email</th>
          <th class="p-2 text-center border">Action</th>
        </tr>
      </thead>
      <tbody>
        @forelse($users['data'] as $user)
        <tr>
          <td class="p-2 border">{{ $loop->index + 1 }}</td>
          <td class="p-2 border">{{ $user['name'] }}</td>
          <td class="p-2 border">{{ $user['email'] }}</td>
          <td class="p-2 text-center border">
            <x-button class="text-white bg-yellow-300 ms-3 outline" wire:click="updateUser({{ $user['id'] }})">
              Edit
            </x-button>

            <x-danger-button class="ms-3 outline"
              type="button"
              wire:click="deleteUser({{ $user['id'] }})"
              wire:confirm="Are you sure you want to delete this user?"
            >
              Delete
            </x-danger-button>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4">
            No record found.
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>
