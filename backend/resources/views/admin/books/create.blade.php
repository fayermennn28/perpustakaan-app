@extends('layouts.admin')

@section('title', 'Tambah Buku')

@section('content')
    <div class="card-soft p-4">
        <form method="POST" action="{{ route('admin.books.store') }}" enctype="multipart/form-data">
            @csrf
            @include('admin.books._form', ['book' => null])
        </form>
    </div>
@endsection