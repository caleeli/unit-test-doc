    <div id="<?php echo htmlentities($indexTitle, ENT_QUOTES); ?>">
        <h3><a href="#<?php echo htmlentities($indexTitle, ENT_QUOTES); ?>"><?php echo $card['title']; ?></a></h3>
        <p><?php echo $card['description']; ?></p>
        <table border="1" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="30%"><b>Class</b></td>
                <td width="70%">
                    <?php echo $card['module']; ?>
                    <p>
                        <i><?php echo $card['module-description']; ?></i>
                    </p>
                </td>
            </tr>
            <tr>
                <td><b>Pre-conditions</b></td>
                <td>
                    <?php foreach ($card['pre-conditions'] as $preCondition => $steps): ?>
                    <?php echo $preCondition; ?>
                    <ol>
                        <?php foreach ($steps as $step): ?>
                        <li><?php echo $step[0]; ?></li>
                        <?php endforeach; ?>
                    </ol>
                    <?php endforeach; ?>
                </td>
            </tr>
        </table>
        <table border="1" cellpadding="0" cellspacing="0" width="100%">
            <tr>
                <td width="60%"><b>Steps</b></td>
                <td width="40%"><b>Expected results</b></td>
            </tr>
            <?php foreach ($card['steps'] as $i => $steps):?>
            <tr>
                <td>
                    <ul>
                        <?php foreach ($steps as $step): ?>
                        <li><?php echo $step[0]; ?>
                            <div class="card bg-light pt-3 mt-3 mb-3 mr-3">
                                <code><?php echo highlightText($step[1]); ?></code>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </td>
                <td><ul><li><?php echo implode('</li><li>', $card['results'][$i]); ?></li></ul></td>
            </tr>
            <?php endforeach; ?>
            <?php if (!empty($card['diagram'])): ?>
            <tr>
                <td colspan="2">
                    <?php echo $card['diagram']; ?>
                </td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
