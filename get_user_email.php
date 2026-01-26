<?php
use App\Models\User;
$user = User::where('name', 'miguel')->first() ?? User::where('id', 2)->first() ?? User::first();
echo $user ? $user->email : "No user found";
