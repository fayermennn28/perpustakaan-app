@extends('layouts.admin')

@section('title', 'Ubah Buku')

@section('content')
    <div class="card-soft p-4">
        <form method="POST" action="{{ route('admin.books.update', $book) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            @include('admin.books._form', ['book' => $book])
        </form>
    </div>
@endsection