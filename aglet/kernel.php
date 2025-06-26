<?php

schedule()->task('Cleanup', '*/10 * * * *', 'Weave\\Tasks\\Cleaner@run');
schedule()->task('Report', '0 6 * * *', 'Weave\\Tasks\\Reporter@daily');
schedule()->task('Report Per minutes', '* * * * *', 'Weave\\Tasks\\Reporter@daily');