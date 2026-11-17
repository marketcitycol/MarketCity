@extends('errors.minimal')
@section('code', '403')
@section('title', 'No tienes acceso')
@section('message', $exception->getMessage() ?: 'Esta sección requiere permisos que tu cuenta no tiene.')
