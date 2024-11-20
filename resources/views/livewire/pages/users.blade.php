<?php

use App\Models\User;
use Illuminate\Http\Request;
use function Livewire\Volt\{layout, title, state, computed};

layout('layouts.app');
title('Users');

state([
  'userId' => 0,
  'name' => '123',
  'email' => '',
  'password' => '',
]);

state([
  'search' => '',
  'sort' => '',
  'order' => 'asc',
  'perPage' => '10',
  'page' => '1',
])->url(history: true);

state(['users' => function () {
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
}]);

$createUser = function () {
  $this->userId = '';
  $this->name = '';
  $this->email = '';

  $this->dispatch('open-modal', 'user-modal');
};

$updateUser = function ($userId) {
  try {
    $response = app(App\Http\Controllers\UserController::class)->show($userId);
    $result = $response->getData(true);
    $status = $response->getStatusCode();

    if($status == 200) {
      // Update the `user` state with the selected user's data
      $this->userId = $result['id'];
      $this->name = $result['name'];
      $this->email = $result['email'];

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

?>

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
            {{ $userId ? 'Update' : 'Create' }} User
          </h2>

          <div class="mt-6">
            <div class="mb-4">
              <x-input-label for="name" :value="__('Name')" />
              <x-text-input wire:model="name" id="name" placeholder="{{ __('Name') }}" class="block w-full mt-1"
                require />
              <x-input-error :messages="$errors->get('name')" class="mt-2" />
            </div>
            <div class="mb-4">
              <x-input-label for="email" :value="__('E-mail')" />
              <x-text-input wire:model="email" id="email" placeholder="{{ __('E-mail') }}" type="email"
                class="block w-full mt-1" required />
              <x-input-error :messages="$errors->get('email')" class="mt-2" />
            </div>
            @if(!$userId)
            <div class="mb-4">
              <x-input-label for="password" :value="__('Password')" />
              <x-text-input wire:model="password" id="password" placeholder="{{ __('Password') }}" type="password"
                class="block w-full mt-1" required />
              <x-input-error :messages="$errors->get('password')" class="mt-2" />
            </div>
            @endif
          </div>

          <div class="flex justify-end mt-6">
            <x-secondary-button x-on:click="$dispatch('close')">
              {{ __('Cancel') }}
            </x-secondary-button>

            <x-primary-button class="ms-3">
              {{ __('Create User') }}
            </x-primary-button>
          </div>
        </form>
      </x-modal>
    </div>
  </div>

  <!-- Session Status -->
  @if(session('status'))
  <div class="w-full p-2 mb-4 overflow-hidden transition duration-300 ease-out" >
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
              <x-button class="text-white bg-yellow-300 ms-3 outline"
                wire:click="updateUser({{ $user['id'] }})"
              >
                Edit
              </x-button>

              <x-danger-button class="ms-3 outline">
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

  {{-- <div class="flex flex-col items-center mt-6 bg-gray-100 sm:justify-center sm:pt-0">
    <div class="w-full px-6 overflow-hidden bg-white shadow-md sm:max-w-md sm:rounded-lg">
      <form wire:submit="store" class="py-6 ">
        <div class="mb-4">
          <x-input-label for="name" :value="__('Name')" />
          <x-text-input wire:model="name" id="name" placeholder="{{ __('Name') }}" class="block w-full mt-1" require />
          <x-input-error :messages="$errors->get('message')" class="mt-2" />
        </div>
        <div class="mb-4">
          <x-input-label for="email" :value="__('E-mail')" />
          <x-text-input wire:model="email" id="email" placeholder="{{ __('E-mail') }}" type="email"
            class="block w-full mt-1" required />
          <x-input-error :messages="$errors->get('message')" class="mt-2" />
        </div>
        <div class="mb-4">
          <x-input-label for="password" :value="__('Password')" />
          <x-text-input wire:model="password" id="password" placeholder="{{ __('password') }}" class="block w-full mt-1"
            required />
          <x-input-error :messages="$errors->get('message')" class="mt-2" />
        </div>

        <x-primary-button class="mt-4">{{ __('Create') }}</x-primary-button>
      </form>
    </div>
  </div> --}}
</div>