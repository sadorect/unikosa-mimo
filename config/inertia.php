<?php

/*
|--------------------------------------------------------------------------
| Inertia
|--------------------------------------------------------------------------
| Only what this application needs to override. Everything else — SSR and
| history settings — is merged in from the package's own defaults.
|
| The one thing that has to be stated here is the page directory. The package
| defaults to resource_path('js/pages') while this application has always used
| 'js/Pages', so the view finder had no valid path registered: every
| assertInertia(->component()) in the suite failed with "page component file
| does not exist", whichever page it was.
*/

return [

    'pages' => [

        'ensure_pages_exist' => false,

        'paths' => [

            resource_path('js/Pages'),

        ],

        'extensions' => [

            'js',
            'jsx',
            'svelte',
            'ts',
            'tsx',
            'vue',

        ],

    ],

];
