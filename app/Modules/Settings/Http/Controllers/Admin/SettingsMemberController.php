<?php

declare(strict_types=1);

namespace Modules\Settings\Http\Controllers\Admin;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Modules\Core\Http\Controllers\ApiController;
use Modules\Settings\Services\MemberService;
use Modules\Settings\Http\Requests\UpdateMemberRequest;
use Modules\Settings\Http\Requests\CreateMemberRequest;
use Modules\Settings\Http\Requests\InviteMemberRequest;
use Modules\Settings\Http\Requests\BulkInviteMemberRequest;
use Modules\Settings\Http\Requests\UpdateMemberRoleRequest;
use Modules\Settings\Http\Requests\UpdateMemberStatusRequest;

class SettingsMemberController extends ApiController
{
    public function __construct(
        private readonly MemberService $memberService
    ) {}

    /**
     * Liste des membres
     */
    public function index(Request $request): JsonResponse
    {
        $filters = [
            'search' => $request->input('search'),
            'role' => $request->input('role'),
            'status' => $request->input('status'),
            'sort_by' => $request->input('sort_by', 'created_at'),
            'sort_direction' => $request->input('sort_direction', 'desc'),
        ];

        $perPage = (int) $request->input('per_page', 15);

        $members = $this->memberService->getMembers($filters, $perPage);

        return $this->successResponse($members, 'Membres récupérés avec succès');
    }

    /**
     * Détail d'un membre
     */
    public function show(string $id): JsonResponse
    {
        $member = $this->memberService->getMember($id);

        return $this->successResponse($member, 'Membre récupéré avec succès');
    }

    /**
     * Créer un membre
     */
    public function store(CreateMemberRequest $request): JsonResponse
    {
        $member = $this->memberService->createMember($request->validated());

        return $this->successResponse(
            $member,
            'Membre créé avec succès',
            201
        );
    }

    /**
     * Mettre à jour un membre
     */
    public function update(UpdateMemberRequest $request, string $id): JsonResponse
    {
        $member = $this->memberService->updateMember($id, $request->validated());

        return $this->successResponse(
            $member,
            'Membre mis à jour avec succès'
        );
    }

    /**
     * Supprimer un membre
     */
    public function destroy(string $id): JsonResponse
    {
        $this->memberService->deleteMember($id);

        return $this->successResponse(
            null,
            'Membre supprimé avec succès'
        );
    }

    /**
     * Mettre à jour le rôle d'un membre
     */
    public function updateRole(UpdateMemberRoleRequest $request, string $id): JsonResponse
    {
        $member = $this->memberService->updateMemberRole($id, $request->validated());

        return $this->successResponse(
            $member,
            'Rôle du membre mis à jour avec succès'
        );
    }

    /**
     * Mettre à jour le statut d'un membre
     */
    public function updateStatus(UpdateMemberStatusRequest $request, string $id): JsonResponse
    {
        $member = $this->memberService->updateMemberStatus($id, $request->validated());

        return $this->successResponse(
            $member,
            'Statut du membre mis à jour avec succès'
        );
    }

    /**
     * Statistiques des membres
     */
    public function statistics(): JsonResponse
    {
        $stats = $this->memberService->getStatistics();

        return $this->successResponse($stats, 'Statistiques récupérées avec succès');
    }

    /**
     * Inviter un membre
     */
    public function invite(InviteMemberRequest $request): JsonResponse
    {
        $invitation = $this->memberService->inviteMember($request->validated());

        return $this->successResponse(
            $invitation,
            'Invitation envoyée avec succès',
            201
        );
    }

    /**
     * Inviter plusieurs membres
     */
    public function bulkInvite(BulkInviteMemberRequest $request): JsonResponse
    {
        $result = $this->memberService->bulkInviteMembers($request->validated());

        return $this->successResponse(
            $result,
            "Invitations envoyées: {$result['success_count']} réussie(s), {$result['error_count']} échouée(s)"
        );
    }

    /**
     * Liste des invitations
     */
    public function invitations(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 15);
        $invitations = $this->memberService->getInvitations($perPage);

        return $this->successResponse($invitations, 'Invitations récupérées avec succès');
    }

    /**
     * Renvoyer une invitation
     */
    public function resendInvitation(string $id): JsonResponse
    {
        $this->memberService->resendInvitation($id);

        return $this->successResponse(
            null,
            'Invitation renvoyée avec succès'
        );
    }

    /**
     * Annuler une invitation
     */
    public function cancelInvitation(string $id): JsonResponse
    {
        $this->memberService->cancelInvitation($id);

        return $this->successResponse(
            null,
            'Invitation annulée avec succès'
        );
    }
}
