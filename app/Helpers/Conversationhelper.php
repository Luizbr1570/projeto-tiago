<?php

namespace App\Helpers;

class ConversationHelper
{
    public static function senderBadge(string $sender): string
    {
        return match($sender) {
            'lead' => 'badge-novo',
            'bot' => 'badge-em_conversa',
            'human' => 'badge-encaminhado',
            default => 'badge-default'
        };
    }

    public static function senderLabel(string $sender): string
    {
        return match($sender) {
            'lead' => 'Lead',
            'bot' => 'Bot (IA)',
            'human' => 'Humano',
            default => ucfirst($sender)
        };
    }

    /**
     * Retorna a cor CSS (rgba) do avatar para cada tipo de remetente.
     * Uso: ConversationHelper::senderColor($conv->sender)
     */
    public static function senderColor(string $sender): string
    {
        return match($sender) {
            'lead'  => 'rgba(168,85,247,0.2)',
            'bot'   => 'rgba(67,233,123,0.2)',
            'human' => 'rgba(236,72,153,0.2)',
            default => 'rgba(100,100,100,0.2)'
        };
    }

    /**
     * Retorna a cor do ícone para cada tipo de remetente.
     * Uso: ConversationHelper::senderIconColor($conv->sender)
     */
    public static function senderIconColor(string $sender): string
    {
        return match($sender) {
            'lead'  => '#a855f7',
            'bot'   => '#43e97b',
            'human' => '#ec4899',
            default => '#888888'
        };
    }

    /**
     * Retorna o nome do ícone Lucide para cada tipo de remetente.
     * Uso: ConversationHelper::senderIcon($conv->sender)
     */
    public static function senderIcon(string $sender): string
    {
        return match($sender) {
            'lead'  => 'user',
            'bot'   => 'bot',
            'human' => 'headphones',
            default => 'message-circle'
        };
    }
}