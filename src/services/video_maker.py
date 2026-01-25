import sys
import os
from moviepy.editor import ImageSequenceClip, AudioFileClip
from moviepy.audio.fx.all import audio_loop
from PIL import Image, ImageOps
import concurrent.futures 

TARGET_SIZE = (1080, 1920)

def process_single_image(img_path):
    """
    Funkcja przetwarzająca jedno zdjęcie.
    Musi być poza główną funkcją, aby działała w multiprocessingu.
    """
    try:
        with Image.open(img_path) as img:
            if img.mode != 'RGB':
                img = img.convert('RGB')

            img_resized = ImageOps.pad(img, TARGET_SIZE, method=Image.Resampling.BICUBIC, color='black')
            
            img_resized.save(img_path, quality=90)
            return img_path
    except Exception as e:
        print(f"Warning: Skipping bad image {img_path}: {e}")
        return None

def create_reel(folder_path, output_path, audio_path=None):
    valid_extensions = ('.jpg', '.jpeg', '.png')
    files = [os.path.join(folder_path, f) for f in sorted(os.listdir(folder_path)) 
             if f.lower().endswith(valid_extensions)]
    
    if not files:
        print("Error: No images found")
        return

    processed_files = []

    # ProcessPoolExecutor automatycznie dobiera liczbę procesów do liczby rdzeni
    with concurrent.futures.ProcessPoolExecutor() as executor:
        results = list(executor.map(process_single_image, files))

    processed_files = [f for f in results if f is not None]

    if not processed_files:
        print("Error: Could not process any images")
        return

    fps = len(processed_files) / 30 # docelowo 30 sekund wideo
    print("Rendering video...")
    clip = ImageSequenceClip(processed_files, fps=fps)
    
    # Audio (opcjonalne)
    if audio_path and os.path.exists(audio_path):
        try:
            audio = AudioFileClip(audio_path)

            # by muzyczka nie szła po skończeniu klipu
            if audio.duration < clip.duration:
                audio = audio_loop(audio, duration=clip.duration)
            else:
                audio = audio.subclip(0, clip.duration)

            clip = clip.set_audio(audio)
        except Exception as e:
            print(f"Warning: Could not load audio {audio_path}: {e}")

    clip.write_videofile(
        output_path,
        codec="libx264",
        audio=True,
        audio_codec="aac",
        fps=24,
        preset="ultrafast",
        threads=4
    )


def create_thumbnail(thumbnail_path):
    try:
        with Image.open(thumbnail_path) as img:
            if img.mode != 'RGB':
                img = img.convert('RGB')

            img_resized = ImageOps.pad(img, TARGET_SIZE, method=Image.Resampling.BICUBIC, color='black')
            img_resized.save(thumbnail_path, quality=90)
    except Exception as e:
        print(f"Error creating thumbnail: {e}")

if __name__ == "__main__":
    if len(sys.argv) < 4:
        print("Usage: python video_maker.py <input_folder> <output_file> <thumbnail_file> [audio_file]")
    else:
        path_to_images = sys.argv[1]
        save_to = sys.argv[2]
        save_thumbnail = sys.argv[3]
        audio_path = sys.argv[4] if len(sys.argv) > 4 else None

        create_reel(path_to_images, save_to, audio_path=audio_path)
        create_thumbnail(save_thumbnail)