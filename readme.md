<h1 align="center"> RoamReel </h1>
<p align="center">
  <img src="https://img.shields.io/badge/Status-Finished-brightgreen">
</p>

<p align="center">
<img width="233" height="270" alt="image" src="https://github.com/user-attachments/assets/f3e1cfed-017f-4f45-a9fd-b47ea77982d0" />
</p>

<h2 align="center">
   An application that allows users to have their own "world gallery" where they can save photos from their trips in the form of 30-second reels.
</h2>

---

## Application Overview

After creating an account and logging in, the user can generate 30-second videos from selected photos.  
Saved videos are then displayed on the user's timeline or in a gallery after selecting a specific country.  
They are sorted chronologically.  
The user can delete a video at any time or edit the date and the country it is assigned to.

---

## Main Features

### Travel Creator
* **Photo Upload**: Drag & Drop support with thumbnail preview before uploading.
* **Video Generation**: Integration between PHP and a Python script that combines uploaded photos into a video (Reel).
<img src="https://github.com/fool-of-all-trades/RoamReel/blob/main/graphics/creator.gif"/>


### Reel sharing
* **Share Your Trip**: Easily share your story to anyone you want with a click of the button.
<img src="https://github.com/fool-of-all-trades/RoamReel/blob/main/graphics/sharing.gif"/>


### Interactive Map
* **SVG Map**: Scalable world map with zoom and pan support (`svg-pan-zoom`).
* **Interaction**: Clicking a country dynamically fetches a list of videos from that region (AJAX) and displays them in the gallery.
<img src="https://github.com/fool-of-all-trades/RoamReel/blob/main/graphics/map.gif"/>


### User Profile
* **Timeline & Statistics**: Overview of travel history.
* **Profile Editing**: Asynchronous profile picture update without reloading the page.
<img src="https://github.com/fool-of-all-trades/RoamReel/blob/main/graphics/timeline.gif"/>



