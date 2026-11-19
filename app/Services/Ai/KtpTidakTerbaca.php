<?php

namespace App\Services\Ai;

use RuntimeException;

/** AI menjawab, tapi foto bukan KTP / tidak terbaca — tidak perlu dicoba ke model cadangan. */
class KtpTidakTerbaca extends RuntimeException {}
