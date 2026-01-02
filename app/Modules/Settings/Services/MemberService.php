<?php

declare(strict_types=1);

namespace Modules\Settings\Services;

use Modules\User\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Modules\Core\Services\BaseService;
use Illuminate\Pagination\LengthAwarePaginator;
use Modules\Settings\Models\Invitation;
use Modules\Settings\Mail\MemberInvitationMail;

class MemberService extends BaseService
{
    /**
     * Récupérer la liste des membres avec filtres
     */
    public function getMembers(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        $query = User::query()
            ->with(['invited_by'])
            ->with(['roles', 'permissions']);

        // Recherche
        if (!empty($filters['search'])) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ILIKE', "%{$search}%")
                    ->orWhere('email', 'ILIKE', "%{$search}%");
            });
        }

        // Filtrer par rôle
        if (!empty($filters['role'])) {
            $query->whereHas('roles', function ($q) use ($filters) {
                $q->where('name', $filters['role']);
            });
        }

        // Filtrer par statut
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        // Tri
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $query->orderBy($sortBy, $sortDirection);

        return $query->paginate($perPage);
    }

    /**
     * Récupérer un membre par ID
     */
    public function getMember(string $id): User
    {
        return User::with(['invited_by', 'permissions'])
            ->findOrFail($id);
    }

    /**
     * Créer un nouveau membre
     */
    public function createMember(array $data): User
    {
        return $this->transaction(function () use ($data) {
            $member = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'phone' => $data['phone'] ?? null,
                'password' => isset($data['password'])
                    ? Hash::make($data['password'])
                    : Hash::make(str()->random(16)),
                'status' => 'active',
            ]);

            // Envoyer une invitation si demandé
            if ($data['send_invitation'] ?? false) {
                $this->sendWelcomeEmail($member);
            }

            $member->assignRole($data['role']);

            $this->logInfo('Membre créé', [
                'member_id' => $member->id,
                'created_by' => Auth::id(),
            ]);

            return $member;
        });
    }

    /**
     * Mettre à jour un membre
     */
    public function updateMember(string $id, array $data): User
    {
        return $this->transaction(function () use ($id, $data) {
            $member = User::findOrFail($id);

            $member->update(array_filter([
                'name' => $data['name'] ?? $member->name,
                'email' => $data['email'] ?? $member->email,
                'username' => $data['username'] ?? $member->username,
                'role' => $data['role'] ?? $member->role,
                'phone' => $data['phone'] ?? $member->phone,
                'status' => $data['status'] ?? $member->status,
            ]));

            $this->logInfo('Membre mis à jour', [
                'member_id' => $member->id,
                'updated_by' => Auth::id(),
            ]);

            return $member->fresh();
        });
    }

    /**
     * Supprimer un membre
     */
    public function deleteMember(string $id): void
    {
        $this->transaction(function () use ($id) {
            $member = User::findOrFail($id);

            // Empêcher la suppression de son propre compte
            if ($member->id === Auth::id()) {
                throw new \Exception('Vous ne pouvez pas supprimer votre propre compte');
            }

            $member->delete();

            $this->logInfo('Membre supprimé', [
                'member_id' => $member->id,
                'deleted_by' => Auth::id(),
            ]);
        });
    }

    /**
     * Mettre à jour le rôle d'un membre
     */
    public function updateMemberRole(string $id, array $data): User
    {
        return $this->transaction(function () use ($id, $data) {
            $member = User::findOrFail($id);

            // Empêcher de modifier son propre rôle
            if ($member->id === Auth::id()) {
                throw new \Exception('Vous ne pouvez pas modifier votre propre rôle');
            }

            $member->syncRoles([$data['role']]);

            $this->logInfo('Rôle du membre mis à jour', [
                'member_id' => $member->id,
                'new_role' => $data['role'],
                'updated_by' => Auth::id(),
            ]);

            return $member->fresh();
        });
    }

    /**
     * Mettre à jour le statut d'un membre
     */
    public function updateMemberStatus(string $id, array $data): User
    {
        return $this->transaction(function () use ($id, $data) {
            $member = User::findOrFail($id);

            // Empêcher de modifier son propre statut
            if ($member->id === Auth::id()) {
                throw new \Exception('Vous ne pouvez pas modifier votre propre statut');
            }

            $member->update([
                'status' => $data['status'],
            ]);

            $this->logInfo('Statut du membre mis à jour', [
                'member_id' => $member->id,
                'new_status' => $data['status'],
                'reason' => $data['reason'] ?? null,
                'updated_by' => Auth::id(),
            ]);

            return $member->fresh();
        });
    }

    /**
     * Statistiques des membres
     */
    public function getStatistics(): array
    {
        $total = User::count();
        $active = User::where('status', 'active')->count();
        $inactive = User::where('status', 'inactive')->count();
        $invited = User::where('status', 'invited')->count();
        $suspended = User::where('status', 'suspended')->count();

        // Par rôle - utilisation de whereHas pour spécifier le guard 'web'
        $admins = User::whereHas('roles', function ($query) {
            $query->where('name', 'admin')
                ->where('guard_name', 'web');
        })->count();

        $superAdmins = User::whereHas('roles', function ($query) {
            $query->where('name', 'super_admin')
                ->where('guard_name', 'web');
        })->count();

        $managers = User::whereHas('roles', function ($query) {
            $query->where('name', 'manager')
                ->where('guard_name', 'web');
        })->count();

        // Activité récente
        $loggedInToday = User::whereDate('last_login_at', today())->count();
        $loggedInThisWeek = User::whereBetween('last_login_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        $loggedInThisMonth = User::whereMonth('last_login_at', now()->month)->count();

        // Nouveaux membres
        $newThisWeek = User::whereBetween('created_at', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ])->count();
        $newThisMonth = User::whereMonth('created_at', now()->month)->count();

        // Invitations
        $pendingInvitations = Invitation::whereNull('accepted_at')
            ->where('expires_at', '>', now())
            ->count();
        $acceptedInvitations = Invitation::whereNotNull('accepted_at')->count();
        $expiredInvitations = Invitation::whereNull('accepted_at')
            ->where('expires_at', '<=', now())
            ->count();

        return [
            'total_members' => $total,
            'active_members' => $active,
            'inactive_members' => $inactive,
            'invited_members' => $invited,
            'suspended_members' => $suspended,

            'admins_count' => $admins,
            'super_admins_count' => $superAdmins, // Ajouté car vous le calculez
            'managers_count' => $managers,

            'online_now' => 0, // Peut être implémenté avec un système de session
            'logged_in_today' => $loggedInToday,
            'logged_in_this_week' => $loggedInThisWeek,
            'logged_in_this_month' => $loggedInThisMonth,

            'pending_invitations' => $pendingInvitations,
            'accepted_invitations' => $acceptedInvitations,
            'expired_invitations' => $expiredInvitations,

            'new_this_week' => $newThisWeek,
            'new_this_month' => $newThisMonth,
        ];
    }

    /**
     * Inviter un membre
     */
    public function inviteMember(array $data): Invitation
    {
        return $this->transaction(function () use ($data) {
            $invitation = Invitation::create([
                'email' => $data['email'],
                'name' => $data['name'] ?? null,
                'role' => $data['role'],
                'token' => str()->random(64),
                'message' => $data['message'] ?? null,
                'invited_by_id' => Auth::id(),
                'expires_at' => now()->addDays(7),
            ]);

            // Envoyer l'email d'invitation
            Mail::to($invitation->email)->send(
                new MemberInvitationMail($invitation)
            );

            $this->logInfo('Invitation envoyée', [
                'invitation_id' => $invitation->id,
                'email' => $invitation->email,
                'invited_by' => Auth::id(),
            ]);

            return $invitation;
        });
    }

    /**
     * Inviter plusieurs membres
     */
    public function bulkInviteMembers(array $data): array
    {
        $successCount = 0;
        $errorCount = 0;
        $errors = [];

        foreach ($data['invitations'] as $invitationData) {
            try {
                $this->inviteMember($invitationData);
                $successCount++;
            } catch (\Exception $e) {
                $errorCount++;
                $errors[] = [
                    'email' => $invitationData['email'],
                    'message' => $e->getMessage(),
                ];
            }
        }

        return [
            'success_count' => $successCount,
            'error_count' => $errorCount,
            'errors' => $errors,
        ];
    }

    /**
     * Récupérer les invitations
     */
    public function getInvitations(int $perPage = 15): LengthAwarePaginator
    {
        return Invitation::with(['invited_by'])
            ->latest()
            ->paginate($perPage);
    }

    /**
     * Renvoyer une invitation
     */
    public function resendInvitation(string $id): void
    {
        $invitation = Invitation::findOrFail($id);

        if ($invitation->accepted_at) {
            throw new \Exception('Cette invitation a déjà été acceptée');
        }

        // Étendre l'expiration
        $invitation->update([
            'expires_at' => now()->addDays(7),
        ]);

        // Renvoyer l'email
        Mail::to($invitation->email)->send(
            new MemberInvitationMail($invitation)
        );

        $this->logInfo('Invitation renvoyée', [
            'invitation_id' => $invitation->id,
        ]);
    }

    /**
     * Annuler une invitation
     */
    public function cancelInvitation(string $id): void
    {
        $invitation = Invitation::findOrFail($id);

        if ($invitation->accepted_at) {
            throw new \Exception('Cette invitation a déjà été acceptée et ne peut pas être annulée');
        }

        $invitation->delete();

        $this->logInfo('Invitation annulée', [
            'invitation_id' => $invitation->id,
        ]);
    }

    /**
     * Envoyer un email de bienvenue
     */
    private function sendWelcomeEmail(User $member): void
    {
        // TODO: Implémenter l'envoi de l'email de bienvenue
    }
}
