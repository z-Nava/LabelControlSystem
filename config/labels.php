<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Label print block size
    |--------------------------------------------------------------------------
    |
    | Maximum number of serial units sent to a printer in a single block.
    | The backend owns this value so operators only confirm progress.
    |
    */
    'print_block_size' => (int) env('LABEL_PRINT_BLOCK_SIZE', 200),
];
