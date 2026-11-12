@extends('layouts.admin')

@section('title', 'Ubah Anggota')

@section('content')
    <div class="card-soft p-4">
        <form method="POST" action="{{ route('admin.members.update', $member) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.members._form', ['member' => $member])
        </form>
    </div>
@endsection