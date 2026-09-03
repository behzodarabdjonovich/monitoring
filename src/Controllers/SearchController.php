<?php

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Request;
use App\Core\Response;
use App\Models\Search;

/**
 * Global qidiruv (item 16) — topbar qidiruv qutisiga ulanadi.
 * Natijalar tur bo'yicha guruhlanadi.
 */
final class SearchController extends Controller
{
    public function index(Request $request): Response
    {
        $term = trim((string) $request->query('q', ''));
        $results = Search::query($term);
        // JSON so'rovi (topbar jonli qidiruv) qo'llab-quvvatlanadi.
        if ($request->query('format') === 'json' || str_contains((string) $request->header('Accept'), 'application/json')) {
            return Response::json([
                'query' => $term,
                'total' => $results['total'],
                'groups' => array_intersect_key($results, Search::groupLabels()),
            ]);
        }
        return $this->view('search.index', [
            'user' => Auth::user(),
            'title' => 'Qidiruv',
            'active' => '',
            'term' => $term,
            'results' => $results,
            'groupLabels' => Search::groupLabels(),
        ]);
    }
}
