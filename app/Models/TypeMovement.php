<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypeMovement extends Model
{
    use HasFactory;

    // Tabla asociada
    protected $table = 'type_movements';

    // Campos que se pueden asignar masivamente
    protected $fillable = ['name','serie'];

    // Relación con movimientos
    public function movements()
    {
        return $this->hasMany(Movement::class, 'type_movement_id');
    }
}
