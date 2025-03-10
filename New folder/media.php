<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dynamic Multi-Track Music Player</title>
    
    <!-- Font Awesome Link for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">

    <style>
        .track {
            margin: 20px 0;
            padding: 20px;
            border: 1px solid #ddd;
            width: 300px;
            text-align: center;
        }
        .buttons {
            margin-top: 10px;
        }
        button {
            margin: 0 5px;
            padding: 10px;
            cursor: pointer;
            font-size: 16px;
            border: none;
            background: none;
            color: #333;
        }
        button:hover {
            color: #007BFF;
        }
        /* Hide the audio element */
        audio {
            display: none;
        }
    </style>
</head>
<body>
    <h1>Meditation</h1>

    <?php
    $audio_dir = 'includes/audio/meditation/';
    $audio_files = glob($audio_dir . '*.mp3');
    
    if (count($audio_files) > 0) {
        foreach ($audio_files as $index => $file_path) {
            $file_name = basename($file_path);
            $file_name_no_ext = pathinfo($file_name, PATHINFO_FILENAME); // Remove file extension
            $file_size = round(filesize($file_path) / 1024, 2) . ' KB';
            $file_id = "audio" . $index;
            
            // Hide file extension in the display
            echo "<div class='track'>
                    <h2>$file_name_no_ext</h2>
                    <audio id='$file_id' src='$file_path'></audio>
                    <div class='buttons'>
                        <button onclick='playAudio(\"$file_id\")'><i class='fas fa-play'></i></button>
                        <button onclick='pauseAudio(\"$file_id\")'><i class='fas fa-pause'></i></button>
                        <button onclick='stopAudio(\"$file_id\")'><i class='fas fa-stop'></i></button>
                        <a href='$file_path' download><button><i class='fas fa-download'></i></button></a>
                    </div>
                    <p id='duration-$file_id'>Duration: Loading...</p>
                </div>";
        }
    } else {
        echo "<p>No audio files found in the directory.</p>";
    }
    ?>

    <script>
        function playAudio(id) {
            const audio = document.getElementById(id);
            audio.play();
        }

        function pauseAudio(id) {
            const audio = document.getElementById(id);
            audio.pause();
        }

        function stopAudio(id) {
            const audio = document.getElementById(id);
            audio.pause();
            audio.currentTime = 0;
        }

        document.addEventListener('DOMContentLoaded', function() {
            const audios = document.querySelectorAll('audio');
            audios.forEach(audio => {
                audio.addEventListener('loadedmetadata', () => {
                    const duration = formatTime(audio.duration);
                    const durationElem = document.getElementById('duration-' + audio.id);
                    durationElem.innerText = 'Duration: ' + duration;
                });
            });
        });

        function formatTime(seconds) {
            const minutes = Math.floor(seconds / 60);
            const secs = Math.floor(seconds % 60);
            return minutes + ":" + (secs < 10 ? '0' : '') + secs;
        }
    </script>

    <script>
        const waveforms = {};

        document.addEventListener('DOMContentLoaded', function() {
            // Initialize Wavesurfer for each audio track
            <?php foreach ($audio_files as $index => $file_path): ?>
                const fileId = 'waveform<?php echo $index; ?>';
                waveforms[fileId] = WaveSurfer.create({
                    container: '#' + fileId,
                    waveColor: '#6c757d',
                    progressColor: '#007BFF',
                    barWidth: 2,
                    cursorColor: '#007BFF',
                    responsive: true,
                    height: 150
                });
                waveforms[fileId].load('<?php echo $file_path; ?>');
            <?php endforeach; ?>
        });

        function playAudio(id) {
            waveforms[id].play();
        }

        function pauseAudio(id) {
            waveforms[id].pause();
        }

        function stopAudio(id) {
            waveforms[id].stop();
        }
    </script>

</body>
</html>
