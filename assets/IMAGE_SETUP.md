# Image Assets Setup Instructions

The application is now configured to use real logo and background images. Follow these steps to complete the setup:

## 1. Logo Image
- **File:** `logo.png` (or `logo.jpg`)
- **Location:** `c:\xampp\htdocs\Sustain-U\assets\logo.png`
- **Expected:** The Sustain-U logo with the library building and water droplet
- **Size:** Recommended 300px wide × 80px tall (or adjust in `css/style.css` `.site-logo` styling)
- **Format:** PNG or JPG

## 2. Background Image
- **File:** `bg.jpg` (or `bg.png`)
- **Location:** `c:\xampp\htdocs\Sustain-U\assets\bg.jpg`
- **Expected:** Campus panoramic/aerial view (the city skyline image)
- **Size:** At least 1600px wide
- **Format:** JPG recommended for fast loading; PNG also supported

## 3. Current Status
✓ Application is fully configured to reference these image files
✓ CSS rules apply fading overlays on app pages (white 92% opacity)
✓ Login pages show background more prominently (30% overlay)
✓ All APIs and tests pass
✓ Ready for image placement

## 4. Next Steps
1. Place the Sustain-U logo file as `assets/logo.png`
2. Place the campus background file as `assets/bg.jpg`
3. Clear browser cache
4. Visit `http://localhost/Sustain-U/` to see the complete UI with images

---

**Files Updated:**
- `inc/header.php` — References `logo.png`
- `css/style.css` — References `bg.jpg` as background
- Database schema updated to support new issue fields
- All tests passing (see `test_smoke.php`)
