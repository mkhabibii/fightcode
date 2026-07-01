@extends('layouts.admin')

@section('content')
    <h2 class="text-xl font-bold mb-4">Kelola Users</h2>


    @if (session('success'))
        <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)"
            class="mb-4 bg-green-100 border border-green-300 text-green-800 px-4 py-2 rounded shadow transition-opacity duration-500">
            {{ session('success') }}
        </div>
    @endif

    <div class="flex justify-between items-center mb-4 flex-wrap gap-2">
        {{-- Filter Kiri --}}
        <form method="GET" class="flex gap-2 items-center">
            <select name="per_page" onchange="this.form.submit()" class="border px-2 py-2 rounded">
                @foreach ([10, 20, 50, 100] as $size)
                    <option value="{{ $size }}" {{ $perPage == $size ? 'selected' : '' }}>{{ $size }} data
                    </option>
                @endforeach
            </select>

            <select name="role" onchange="this.form.submit()" class="border px-2 py-2 rounded">
                <option value="">Semua Role</option>
                @foreach ($roles as $r)
                    <option value="{{ $r }}" {{ $role == $r ? 'selected' : '' }}>{{ ucfirst($r) }}</option>
                @endforeach
            </select>
        </form>

        {{-- Seatcing --}}
        <form method="GET" class="relative flex-1 max-w-xs">
            <input type="text" name="search" placeholder="Cari user..." value="{{ $search }}"
                class="border rounded pl-10 py-1.5 px-3 w-full oninput="this.form.submit()">
            <span class="absolute left-3 top-2.5 text-gray-500">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-4.35-4.35A7.5 7.5 0 104.5 4.5a7.5 7.5 0 0012.15 12.15z" />
                </svg>
            </span>
        </form>

        {{-- Tombol Export Kanan --}}
        {{-- <div class="ml-auto flex ">
            <a href="{{ route('admin.users.export.excel') }}"
                class="bg-green-500 text-white px-3 py-2 rounded text-sm hover:bg-green-600">Export Excel</a>
        </div> --}}
    </div>

    <div class="overflow-x-auto bg-white shadow rounded">
        <table class="min-w-full text-sm text-left">
            <thead class="bg-[#088395] text-white ">
                <tr class="drop-shadow-[0px_1px_0px_black] ">
                    <th class="px-4 py-3">No</th>
                    <th class="px-4 py-3">Avatar</th>
                    <th class="px-4 py-3">Nama</th>
                    <th class="px-4 py-3">Email</th>
                    <th class="px-4 py-3">No HP</th>
                    <th class="px-4 py-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $index => $user)
                    <tr class="border-b hover:bg-gray-50">
                        <td class="px-4 py-2">{{ ($users->currentPage() - 1) * $users->perPage() + $index + 1 }}</td>
                        <td class="px-4 py-2">
                            @php
                                $avatar = $user->avatar;
                                $avatarUrl = Str::startsWith($avatar, 'http')
                                    ? $avatar
                                    : ($avatar
                                        ? asset('storage/avatar/' . $avatar)
                                        : 'https://ui-avatars.com/api/?name=' .
                                            urlencode($user->name) .
                                            '&size=128&background=0D8ABC&color=fff');
                            @endphp

                            <img src="{{ $avatarUrl }}" class="w-10 h-10 rounded-full object-cover"
                                alt="{{ $user->name }}">

                        </td>
                        <td class="px-4 py-2">{{ $user->name }}</td>
                        <td class="px-4 py-2">{{ $user->email }}</td>
                        <td class="px-4 py-2">{{ $user->no_telp ?? '-' }}</td>

                        <td class="px-4 py-2">
                            <form method="POST" action="{{ route('admin.users.destroy', $user) }}" class="delete-form">
                                @csrf
                                @method('DELETE')
                                <button type="button" class="text-red-600 hover:underline delete-button">
                                    <iconify-icon icon="solar:trash-bin-trash-linear" width="24"
                                        height="24"></iconify-icon>
                                </button>
                            </form>
                        </td>

                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center font-semibold px-4 py-3 text-gray-500">Tidak ada data.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="mt-4">
        {{ $users->links() }}
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const deleteButtons = document.querySelectorAll('.delete-button');

            deleteButtons.forEach(button => {
                button.addEventListener('click', function(e) {
                    const form = button.closest('form');

                    Swal.fire({
                        title: 'Apakah kamu yakin?',
                        text: "User akan dihapus secara permanen!",
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#e3342f',
                        cancelButtonColor: '#6c757d',
                        confirmButtonText: 'Ya, hapus!',
                        cancelButtonText: 'Batal'
                    }).then((result) => {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection
