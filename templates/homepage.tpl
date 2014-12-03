	<table class="layout-table">
		<tr>
			<td style="width: 65%; vertical-align: top; padding-right: 0.5em;">
				<table class="outline margin homepage">
					<tr class="header1">
						<th>Home</th>
					</tr>
					<tr class="cell1">
						<td style="padding:5px;">
							{$homepage}
						</td>
					</tr>
				</table>
			</td>
			<td style="width: 35%; vertical-align: top; padding-left: 0.5em;">
				<table class="outline margin lastactivity">
					<tr class="header1">
						<th>Latest Posts</th>
					</tr>
					{foreach $lastactivity as $item}
					<tr class="cell{cycle values='0,1'}">
						<td style="padding:5px;">
							{$item.description}<br>
							<span class="smallFonts">{$item.formattedDate}</span>
						</td>
					</tr>
					{/foreach}
				</table>
			</td>
		</tr>
	</table>
