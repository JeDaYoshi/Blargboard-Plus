	<table class="outline margin">
		<tr class="header1">
			<th>
				Board Configuration
			</th>
		</tr>
		<tr class="cell0">
			<td style="text-align:center;padding:5px;">
				All the basic settings are under General Settings. That's probably the best place to start.<br><br>
				{foreach $adminConfig as $link}
					{$link}
				{/foreach}
			</td>
		</tr>
	</table>
	<table class="outline margin">
		<tr class="header1">
			<th>
				Admin Utilities
			</th>
		</tr>
		<tr class="cell0">
			<td style="text-align:center;padding:5px;">
				{foreach $adminTools as $link}
					{$link}
				{/foreach}<br><br>
				Looking to update Blargboard Plus to the latest version? Go <a href="./tools/update.php">here</a>.
			</td>
		</tr>
	</table>