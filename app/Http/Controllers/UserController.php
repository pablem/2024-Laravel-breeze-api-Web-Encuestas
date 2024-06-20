<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Http\Requests\ProfileUpdateRequest;
use App\Http\Requests\StoreUserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::all();
        return response()->json($users, 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //sin uso
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUserRequest $request)
    {
        // Verificar si el usuario autenticado es un administrador
        if (Auth::user()->role !== UserRole::Administrador->value) {
            return response()->json(['error' => 'No autorizado para crear un usuario'], 403);
        }

        try {
            User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => $request->role ?? UserRole::Publicador->value, // Asignar rol predeterminado si no se proporciona
            ]);
            return response()->json(['success' => 'Usuario creado correctamente'], 201);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function showProfile()
    {
        try {
            $usuario = auth()->user();
            if (!$usuario) {
                return response()->json(['error' => 'Perfil no encontrado'], 404);
            }
            return response()->json($usuario, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show($userId)
    {
        try {
            $usuario = User::findOrFail($userId);
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
            return response()->json($usuario, 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    public function update(ProfileUpdateRequest $request, $userId = null)
    {
        try {
            $usuario = $userId ? User::findOrFail($userId) : auth()->user();
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
            $usuario->name = $request->input('name', $usuario->name);
            $usuario->email = $request->input('email', $usuario->email);
            if ($request->filled('password')) {
                $usuario->password = Hash::make($request->input('password'));
            }
            if ($request->filled('role') && auth()->user()->role === UserRole::Administrador->value) {
                $usuario->role = $request->input('role');
            }
            $usuario->save();
            return response()->json(['success' => 'Usuario actualizado correctamente'], 200);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($userId)
    {
        try {
            $usuario = User::findOrFail($userId);
            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 404);
            }
            $usuario->delete();
            return response()->json(['success' => 'Usuario eliminado correctamente']);
        } catch (\Throwable $th) {
            return response()->json(['error' => $th->getMessage()], 500);
        }
    }
}
