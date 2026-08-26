<?php

declare(strict_types=1);

namespace Modules\Admin\App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Table;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;

/**
 * Modelo que almacena el historial de inicios de sesión del personal (Staff).
 *
 * Registra información de cada intento de inicio de sesión, como la dirección IP,
 * el agente de usuario y detalles del dispositivo, para mejorar la seguridad y
 * permitir la detección de actividades sospechosas.
 *
 * @property int $id
 * @property string|null $loginable_type Tipo polimórfico del modelo asociado.
 * @property int|null $loginable_id ID polimórfico del modelo asociado.
 * @property string|null $ip_address Dirección IP desde la que se inició sesión.
 * @property string|null $user_agent Agente de usuario del navegador.
 * @property string|null $device_type Tipo de dispositivo (ej. 'desktop', 'tablet').
 * @property string|null $browser Navegador utilizado.
 * @property string|null $platform Plataforma o sistema operativo.
 * @property bool $is_mobile Indica si el dispositivo es móvil.
 * @property bool $is_trusted Indica si el dispositivo es de confianza.
 * @property \Carbon\CarbonInterface|null $last_login_at Fecha y hora del último inicio de sesión.
 * @property int $login_count Contador de inicios de sesión desde este dispositivo.
 *
 * @use HasFactory<Factory<StaffUserLoginInfo>>
 */
#[Fillable([
    'loginable_type',
    'loginable_id',
    'ip_address',
    'user_agent',
    'device_type',
    'browser',
    'platform',
    'is_mobile',
    'is_trusted',
    'last_login_at',
    'login_count',
])]
#[Table(name: 'staff_login_infos')]
final class StaffUserLoginInfo extends Model
{
    /** @use HasFactory<Factory<StaffUserLoginInfo>> */
    use HasFactory;

    /**
     * Umbral de similitud para la comparación de agentes de usuario.
     * Se usa para tolerar variaciones menores en las versiones de los navegadores.
     */
    private const int USER_AGENT_SIMILARITY_THRESHOLD = 80;

    /**
     * Los atributos que deberían ser convertidos a tipos nativos.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'is_mobile' => 'boolean',
        'is_trusted' => 'boolean',
        'last_login_at' => 'datetime',
        'login_count' => 'integer',
    ];

    /**
     * Define la relación polimórfica con el modelo dueño de este inicio de sesión.
     *
     * @return MorphTo<Model, $this>
     */
    public function loginable(): MorphTo
    {
        return $this->morphTo('loginable');
    }

    /**
     * Determina si los datos de un nuevo inicio de sesión coinciden con este registro.
     *
     * Compara la dirección IP de forma exacta y el agente de usuario con un umbral
     * de similitud para tolerar pequeñas variaciones en las versiones del navegador.
     *
     * @param  string|null  $ip  La dirección IP del nuevo inicio de sesión.
     * @param  string|null  $userAgent  El agente de usuario del nuevo inicio de sesión.
     */
    public function matches(?string $ip, ?string $userAgent): bool
    {
        if ($ip !== $this->ip_address) {
            return false;
        }

        $matchesUserAgent = $userAgent === $this->user_agent;

        // Si el user agent es nulo en cualquiera de los dos, no coincide
        if (! $matchesUserAgent && $userAgent !== null && $this->user_agent !== null) {
            similar_text($userAgent, $this->user_agent, $percent);
            $matchesUserAgent = $percent >= self::USER_AGENT_SIMILARITY_THRESHOLD;
        }

        return $matchesUserAgent;
    }
}
