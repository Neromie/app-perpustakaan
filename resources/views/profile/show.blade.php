@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <h1>Profil Saya</h1>

    <table>
        <tr>
            <th style="width: 180px;">Nama</th>
            <td>{{ $user->name }}</td>
        </tr>
        <tr>
            <th>Email</th>
            <td>{{ $user->email }}</td>
        </tr>
        <tr>
            <th>Role</th>
            <td><span class="badge badge-{{ $user->role === 'admin' ? 'dikembalikan' : 'dipinjam' }}">{{ ucfirst($user->role) }}</span></td>
        </tr>
        <tr>
            <th>Terdaftar Sejak</th>
            <td>{{ $user->created_at->format('d M Y') }}</td>
        </tr>
    </table>

    <h2 style="margin-top: 40px;">Ganti Password</h2>

    <form action="{{ route('profil.password') }}" method="POST">
        @csrf
        @method('PUT')

        <label for="current_password">Password Lama</label>
        <input type="password" name="current_password" id="current_password">
        @error('current_password')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="password">Password Baru</label>
        <input type="password" name="password" id="password">
        @error('password')
            <div class="error">{{ $message }}</div>
        @enderror

        <label for="password_confirmation">Konfirmasi Password Baru</label>
        <input type="password" name="password_confirmation" id="password_confirmation">

        <p><button type="submit" class="btn">Simpan Password Baru</button></p>
    </form>
@endsection