<?php defined('SYSPATH') or die('No direct script access.') ?>

<style type="text/css">
#mesh table { width: 99%; margin: 0 auto 2em auto; border-collapse: collapse; }
#mesh table th.field_name,
#mesh table th.title { font-size: 1.4em; background: #222; color: #eee; border-color: #222; text-align: left; }
#mesh table th.heading { font-weight: bold; font-size: 1.2em; }
#mesh table th.field_name { font-size: 1.2em; }
#mesh table th,
#mesh table td { padding: 0.2em 0.4em; background: #fff; border: solid 1px #ccc; text-align: center; font-weight: normal; font-size: 1.2em; color: #111; vertical-align: top; }
#mesh table tr.exclude td { color: #a4a4a4; }
#mesh table span { font-weight: bold; }
#mesh table span.passed { color: #00cc00; }
#mesh table span.failed { color: #cc0000; }
</style>

<?php
$debug = Mesh::debug();
if($debug !== array())
{
?>
<div id="mesh">
	<table>
		<thead>
			<tr>
				<th class="title" colspan="4">Mesh</th>
			</tr>
		</thead>
		<tbody>
			<tr>
				<th class="heading">Type</th>
				<th class="heading">Function</th>
				<th class="heading">Value</th>
				<th class="heading">Result</th>
			</tr>
			<?php foreach ($debug as $field_name => $field_debug): ?>
			<tr>
				<th class="field_name" colspan="4"><?php echo "$field_name ({$field_debug[0]['for']})"; ?></th>
			</tr>
			<?php foreach ($field_debug as $key => $value): ?>
			<tr<?php if($value['exclude']) { echo ' class="exclude"'; }?>>
				<td><?php echo $value['type']; ?></td>
				<td><?php echo $value['function']; ?></td>
				<td><?php var_dump($value['value']); ?></td>
				<td>
				<?php
					if($value['exclude'])
					{
						echo '<span>SKIPPED</span>';
					}
					elseif(($value['type'] === 'rule') || ($value['type'] === 'format'))
					{
						echo ($value['passed'] ? '<span class="passed">PASSED</span>' : '<span class="failed">FAILED</span>');
					}
					else
					{
						echo 'N/A';
					}
				?>
				</td>
			</tr>
			<?php endforeach ?>
			<?php endforeach ?>
		</tbody>
	</table>
</div>
<?php
}
?>