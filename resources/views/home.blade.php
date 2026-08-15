@extends('layouts.app') 
@section('content')

    <main> 
        <x-sidebar :chats="$chats" />

        <x-chat
            :chat="$chat" 
            :messages="$messages"/>
    
    </main> 

@endsection