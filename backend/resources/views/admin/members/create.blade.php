@extends('layouts.admin')

@section('title', 'Tambah Anggota')

@section('content')
    <div class="card-soft p-4">
        <form method="POST" action="{{ route('admin.members.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.members._form', ['member' => null])
        </form>
    </div>
@endsection