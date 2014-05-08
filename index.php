<?php

$batch_size = 100;

require('./src/snapchat.php');

$ext = array(
	'image/jpg' => 'jpg',
	'image/jpeg' => 'jpg',
	'image/gif' => 'gif',
	'image/x-png' => 'png',
	'image/png' => 'png'
);

function set_not_empty(&$string)
{
	if( isset($string) && !empty($string) )
	{
		return true;
	}

	return false;
}

if( isset($_POST['snapchat']) )
{
	if(
		set_not_empty($_POST['snapchat']['locus'])
		&& set_not_empty($_POST['snapchat']['username'])
		&& set_not_empty($_POST['snapchat']['password'])
	)
	{
		//print_r($_POST['snapchat']);

		$locus = $_POST['snapchat']['locus'];
		$username = $_POST['snapchat']['username'];
		$password = $_POST['snapchat']['password'];

		$snapchat = new Snapchat($username, $password);

		switch($locus)
		{
			case 'count':
				$friend_count = count( $snapchat->getFriends(true) );
				break;
			case 'mass':
				$seconds = 10;
				if( set_not_empty($_POST['snapchat']['seconds']) )
					$seconds = $_POST['snapchat']['seconds'];
				if( isset($_FILES['snapchat']) )
				{
					if( isset( $ext[ $_FILES['snapchat']['type']['file'] ] ) )
					{
						$file = sprintf('uploads/%s-%s.%s', $username, date('Y-m-d-G-i-s'), $ext[$_FILES['snapchat']['type']['file'] ]);

						move_uploaded_file(
							$_FILES['snapchat']['tmp_name']['file'],
							$file
						);
						
						$friends = $snapchat->getFriends(true);

						$b = 0;
						$i = 1;					
						$batches[$b][] = $username;

						foreach($friends as $friend)
						{
							$batches[$b][] = $friend->name;
							$i++;

							if( $i % $batch_size == 0 )
							{
								$b++;
							}

						}

						send_batches($snapchat, $batches, $file, $seconds);

						if( set_not_empty($_POST['snapchat']['story']) )
						{
							if($_POST['snapchat']['story'] == '1')
							{
								$id = $snapchat->upload(
									Snapchat::MEDIA_IMAGE,
									file_get_contents($file)
								);
								$snapchat->setStory($id, Snapchat::MEDIA_IMAGE, $seconds);
							}
						}

						$friend_count = count($friends);

					} else
					{
						$errors[] = "The uploaded file was not an image.";
					}
				}
				else
				{
					$errors[] = "No images were uploaded with your request.";
				}
				break;
		}
	}
	else
	{
		$errors[] = "username, password, or locus not defined.";
	}
}

function send_batches(&$snapchat, $batches, $file, $seconds = 10)
{
	foreach( $batches as $batch )
	{
		$id = $snapchat->upload(
			Snapchat::MEDIA_IMAGE,
			file_get_contents($file)
		);
		$snapchat->send($id, $batch, $seconds);
	}
}



?><!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, user-scalable=no">
	<title>Snapchat Workbench</title>

	<link rel="stylesheet" href="//netdna.bootstrapcdn.com/bootstrap/3.1.1/css/bootstrap.min.css">
</head>
<body>
	<div class="container">
		<div class="row">
			<div class="col-md-12">
				<h1>Snapchat Workbench</h1>
			</div>
		</div>
<?php
	if( isset($friend_count) )
	{
		echo sprintf(
			'<div class="row"><div class="col-md-12"><div class="alert alert-info">You currently have %u friends on Snapchat.</p></div></div></div>', $friend_count);
	}
?>
		<form method="post" enctype="multipart/form-data">
			<div class="row">

				<div class="col-md-12">
					<div class="form-inline">
						<div class="form-group">
							<label for="mass-username" class="sr-only">Snapchat Username</label>
							<input type="text" id="mass-username" name="snapchat[username]" class="form-control" placeholder="username">
						</div>

						<div class="form-group">
							<label for="mass-password" class="sr-only">Snapchat Password</label>
							<input type="password" id="mass-password" name="snapchat[password]" class="form-control" placeholder="password">
						</div>

							<button type="submit" class="btn btn-primary" name="snapchat[locus]" value="count">Count Friends</button>
					</div>
				</div>


			</div>

			<div class="row">

				<div class="col-md-6">
					<h3>Send A Mass Picture Snap</h3>							

						<div class="form-group">
							<label for="mass-file">Upload a Picture</label>
							<input type="file" id="mass-file" name="snapchat[file]">
							<p class="help-block">Recommended size is 640 &times; 1136 pixels.</p>
						</div>
						<div class="form-group">
							<label for="mass-seconds">How Many Seconds?</label>
							<select id="mass-seconds" name="snapchat[seconds]" class="form-control">
								<?php
									for($i = 1; $i < 11; $i++)
									{
										$selected = ($i == 10)? ' selected="selected"' : '';
										echo sprintf('<option value="%1$s"%2$s>%1$s</option>', $i, $selected);
									}
								?>
							</select>
						</div>
						<div class="checkbox">
							<label>
								<hidden name="snapchat[story]" value="0">
								<input type="checkbox" name="snapchat[story]" value="1">
								Add to Story?
							</label>
						</div>

						<div class="form-group">
							<button type="submit" class="btn btn-primary" name="snapchat[locus]" value="mass">Send Snap</button>
						</div>
				</div>

				<div class="col-md-6">

				</div>

			</div>

			<div class="row">
				<div class="col-md-12">
					<h2>Output</h2>
					<h3>Errors</h3>
					<?php
						if( isset($errors) )
						{
							echo '<ul>';
							foreach ($errors as $error)
							{
								echo sprintf('<li>%s</li>', $error);
							}
							echo '</ul>';
						}
						else
						{
							echo '<em>There were no errors processing this request.</em>';
						}
					?>
					<h3>Batches</h3>
					<?php

						if( isset($batches) )
						{
							$b = 1;
							foreach($batches as $batch)
							{
								echo sprintf('<h4>Batch %u</h4>', $b);
								echo '<ol>';
								foreach ($batch as $recipient)
								{
									echo sprintf('<li>%s</li>', $recipient);
								}
								echo '</ol>';
								$b++;
							}
						}
						else
						{
							echo '<em>There were no batches processed this request.</em>';
						}
					?>
				</div>
			</div>
		</form>
	</div>

</body>
</html>