<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

/**
 * Dashboard Service
 * Orchestrates data fetching from multiple tables for the dashboard view
 */
class DashboardService
{
    use LocatorAwareTrait;

    /**
     * Get all dashboard data for a user
     * 
     * @param int $userId
     * @return array
     */
    public function getDashboardData(int $userId): array
    {
        $usersTable = $this->getTableLocator()->get('Users');
        $postsTable = $this->getTableLocator()->get('Posts');
        
        return [
            'user' => $usersTable->getFormatted($userId),
            'postsArray' => $postsTable->getPostsWithEngagement($userId)
        ];
    }
}
