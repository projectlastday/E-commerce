<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Native PHP Hybrid Recommendation Engine Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for the 100% native Laravel Hybrid Recommendation System:
    | Content-Based Filtering (CBF), Collaborative Filtering (CF), and
    | Maximal Marginal Relevance (MMR) Diversification.
    |
    */

    'enabled' => true,

    // Hybrid Blending Weights (Default: 50% CBF, 50% CF for mature users)
    'weights' => [
        'cbf' => 0.50,
        'cf' => 0.50,
    ],

    // Interaction threshold to consider a user "warm" vs "cold"
    'cold_start_threshold' => 3,

    // Diversity trade-off parameter lambda for MMR (0.0 = max diversity, 1.0 = max relevance)
    'mmr_lambda' => 0.70,

    // Implicit feedback event weights (Hu, Koren, & Volinsky 2008)
    'event_weights' => [
        'click' => 0.5,
        'view_item' => 1.0,
        'view_item_deep' => 2.0, // Dwell time > 15s
        'search_click' => 1.5,
        'add_to_wishlist' => 2.0,
        'add_to_cart' => 3.0,
        'begin_checkout' => 4.0,
        'purchase' => 5.0,
    ],

    // Confidence scaling factor (Hu et al. 2008: c_ui = 1 + alpha * r_ui)
    'alpha_confidence' => 40.0,
];
