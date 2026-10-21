#!/usr/bin/env python3
import os
import math
import subprocess
from PIL import Image, ImageDraw, ImageFont, ImageFilter

WIDTH = 1920
HEIGHT = 1080
FPS = 30

FONT_BOLD = '/System/Library/Fonts/Supplemental/Arial Bold.ttf'
FONT_REGULAR = '/System/Library/Fonts/Supplemental/Arial.ttf'

font_title_large = ImageFont.truetype(FONT_BOLD, 54)
font_title_med = ImageFont.truetype(FONT_BOLD, 36)
font_subtitle = ImageFont.truetype(FONT_REGULAR, 24)
font_badge = ImageFont.truetype(FONT_BOLD, 18)
font_overlay_title = ImageFont.truetype(FONT_BOLD, 28)
font_overlay_desc = ImageFont.truetype(FONT_REGULAR, 20)
font_counter = ImageFont.truetype(FONT_BOLD, 16)

def ease_in_out(t):
    return 0.5 * (1 - math.cos(math.pi * t))

def draw_cursor(draw, x, y, clicking=False):
    pts = [(x, y), (x + 6, y + 22), (x + 11, y + 16), (x + 20, y + 20), (x + 23, y + 17), (x + 14, y + 13), (x + 22, y + 10)]
    draw.polygon(pts, fill=(22, 163, 74), outline=(255, 255, 255))
    if clicking:
        draw.ellipse([x - 12, y - 12, x + 12, y + 12], outline=(74, 222, 128), width=3)

def make_title_card(title, subtitle, badge_text="MAMA BAZAR PLATFORM TOUR", duration_sec=4.0):
    total_frames = int(duration_sec * FPS)
    frames = []
    for f in range(total_frames):
        t = f / float(total_frames)
        img = Image.new('RGB', (WIDTH, HEIGHT), (11, 20, 32))
        draw = ImageDraw.Draw(img)
        
        # Subtle ambient circles
        cx1 = int(WIDTH * 0.3 + math.sin(t * math.pi) * 80)
        cy1 = int(HEIGHT * 0.4 + math.cos(t * math.pi) * 40)
        draw.ellipse([cx1 - 350, cy1 - 350, cx1 + 350, cy1 + 350], fill=(16, 75, 45))
        
        cx2 = int(WIDTH * 0.7 - math.sin(t * math.pi) * 80)
        cy2 = int(HEIGHT * 0.6 - math.cos(t * math.pi) * 40)
        draw.ellipse([cx2 - 400, cy2 - 400, cx2 + 400, cy2 + 400], fill=(15, 34, 56))
        
        img = img.filter(ImageFilter.GaussianBlur(80))
        draw = ImageDraw.Draw(img)
        
        # Card container
        card_w, card_h = 1000, 480
        x1 = (WIDTH - card_w) // 2
        y1 = (HEIGHT - card_h) // 2
        draw.rounded_rectangle([x1, y1, x1 + card_w, y1 + card_h], radius=24, fill=(15, 23, 42), outline=(34, 197, 94), width=2)
        
        # Top Badge
        bw, bh = 340, 38
        bx = (WIDTH - bw) // 2
        by = y1 + 50
        draw.rounded_rectangle([bx, by, bx + bw, by + bh], radius=19, fill=(21, 128, 61))
        draw.text((bx + 24, by + 9), badge_text, font=font_badge, fill=(255, 255, 255))
        
        # Main Title
        draw.text(((WIDTH - draw.textlength(title, font=font_title_large)) // 2, by + 75), title, font=font_title_large, fill=(255, 255, 255))
        
        # Subtitle
        draw.text(((WIDTH - draw.textlength(subtitle, font=font_subtitle)) // 2, by + 160), subtitle, font=font_subtitle, fill=(148, 163, 184))
        
        # Divider line
        lx = (WIDTH - 400) // 2
        draw.line([lx, by + 230, lx + 400, by + 230], fill=(30, 41, 59), width=2)
        
        # Features pill
        pills_text = "Verified Layout • End-to-End Workflows • Full HD 1080p"
        draw.text(((WIDTH - draw.textlength(pills_text, font=font_counter)) // 2, by + 250), pills_text, font=font_counter, fill=(74, 222, 128))
        
        # Fade in/out
        alpha = 1.0
        if f < 15:
            alpha = f / 15.0
        elif f > total_frames - 15:
            alpha = (total_frames - f) / 15.0
        if alpha < 1.0:
            black = Image.new('RGB', (WIDTH, HEIGHT), (0, 0, 0))
            img = Image.blend(black, img, alpha)
            
        frames.append(img)
    return frames

def make_scene_motion(img_path, category, title, desc, duration_sec=5.0, motion_type='zoom', cursor_path=None):
    total_frames = int(duration_sec * FPS)
    raw = Image.open(img_path).convert('RGB')
    rw, rh = raw.size
    frames = []
    
    for f in range(total_frames):
        progress = f / float(total_frames)
        e = ease_in_out(progress)
        
        # Cinematic Ken Burns Camera
        if motion_type == 'zoom':
            scale = 1.0 + (0.12 * e)
            crop_w = int(rw / scale)
            crop_h = int(rh / scale)
            cx = int(rw * 0.5)
            cy = int(rh * 0.45)
            box = (cx - crop_w // 2, cy - crop_h // 2, cx + crop_w // 2, cy + crop_h // 2)
            cropped = raw.crop(box)
        elif motion_type == 'pan_down':
            scale = 1.05
            crop_w = int(rw / scale)
            crop_h = int(rh / scale)
            cx = rw // 2
            y_start = int(rh * 0.15)
            y_end = int(rh * 0.45)
            cy = int(y_start + (y_end - y_start) * e)
            box = (cx - crop_w // 2, cy - crop_h // 2, cx + crop_w // 2, cy + crop_h // 2)
            cropped = raw.crop(box)
        elif motion_type == 'pan_right':
            scale = 1.06
            crop_w = int(rw / scale)
            crop_h = int(rh / scale)
            x_start = int(rw * 0.38)
            x_end = int(rw * 0.58)
            cx = int(x_start + (x_end - x_start) * e)
            cy = int(rh * 0.45)
            box = (cx - crop_w // 2, cy - crop_h // 2, cx + crop_w // 2, cy + crop_h // 2)
            cropped = raw.crop(box)
        else: # static gentle drift
            scale = 1.04 + (0.04 * e)
            crop_w = int(rw / scale)
            crop_h = int(rh / scale)
            box = ((rw - crop_w)//2, (rh - crop_h)//2, (rw + crop_w)//2, (rh + crop_h)//2)
            cropped = raw.crop(box)
            
        frame = cropped.resize((WIDTH, HEIGHT), Image.Resampling.LANCZOS)
        draw = ImageDraw.Draw(frame)
        
        # Virtual Animated Cursor
        if cursor_path:
            p0, p1, click_at = cursor_path
            cur_x = int(p0[0] + (p1[0] - p0[0]) * e)
            cur_y = int(p0[1] + (p1[1] - p0[1]) * e)
            is_click = (click_at - 0.1) <= progress <= (click_at + 0.1)
            draw_cursor(draw, cur_x, cur_y, clicking=is_click)
            
        # Sleek Glassmorphism Lower-Third Motion Card
        overlay_w = 1180
        overlay_h = 110
        ox = (WIDTH - overlay_w) // 2
        oy = HEIGHT - 150
        
        # Card shadow & background
        draw.rounded_rectangle([ox - 2, oy - 2, ox + overlay_w + 2, oy + overlay_h + 2], radius=16, fill=(15, 23, 42))
        draw.rounded_rectangle([ox, oy, ox + overlay_w, oy + overlay_h], radius=14, fill=(15, 23, 42), outline=(22, 163, 74), width=2)
        
        # Category Tag Badge
        tag_bg = (21, 128, 61) if 'ADMIN' not in category else (217, 119, 6)
        draw.rounded_rectangle([ox + 20, oy + 16, ox + 220, oy + 44], radius=6, fill=tag_bg)
        draw.text((ox + 30, oy + 22), category, font=font_badge, fill=(255, 255, 255))
        
        # Step Title
        draw.text((ox + 240, oy + 18), title, font=font_overlay_title, fill=(255, 255, 255))
        
        # Description
        draw.text((ox + 240, oy + 56), desc, font=font_overlay_desc, fill=(148, 163, 184))
        
        # Progress Bar within lower-third
        bar_len = int((overlay_w - 40) * progress)
        draw.rounded_rectangle([ox + 20, oy + overlay_h - 8, ox + 20 + bar_len, oy + overlay_h - 4], radius=2, fill=(74, 222, 128))
        
        # Smooth cross-fade between scenes
        alpha = 1.0
        if f < 10:
            alpha = f / 10.0
        elif f > total_frames - 10:
            alpha = (total_frames - f) / 10.0
        if alpha < 1.0:
            black = Image.new('RGB', (WIDTH, HEIGHT), (10, 15, 25))
            frame = Image.blend(black, frame, alpha)
            
        frames.append(frame)
        
    return frames

def compile_video(scenes, output_path, music_path=None):
    print(f"Compiling video to {output_path}...")
    cmd = [
        'ffmpeg', '-y', '-f', 'rawvideo', '-vcodec', 'rawvideo',
        '-s', f'{WIDTH}x{HEIGHT}', '-pix_fmt', 'rgb24', '-r', str(FPS),
        '-i', '-'
    ]
    
    if music_path and os.path.exists(music_path):
        # Calculate duration of frames
        total_frames = sum(len(s) for s in scenes)
        duration_sec = total_frames / FPS
        cmd.extend([
            '-i', music_path,
            '-filter_complex', f'[1:a]volume=0.8,afade=t=in:ss=0:d=2,afade=t=out:st={duration_sec-3:.1f}:d=3[a]',
            '-map', '0:v', '-map', '[a]',
            '-c:a', 'aac', '-b:a', '192k'
        ])
        
    cmd.extend([
        '-c:v', 'libx264', '-preset', 'fast', '-crf', '21',
        '-pix_fmt', 'yuv420p', output_path
    ])
    
    proc = subprocess.Popen(cmd, stdin=subprocess.PIPE)
    for scene in scenes:
        for frame in scene:
            proc.stdin.write(frame.tobytes())
            
    proc.stdin.close()
    proc.wait()
    print(f"Finished {output_path} (Exit code {proc.returncode}, Size: {os.path.getsize(output_path)} bytes)")

def main():
    music = 'resources/audio/background_music.mp3'
    
    # 1. INTRO TITLE
    intro_full = make_title_card("MAMA BAZAR", "Complete E-Commerce Feature Walkthrough (Frontend + Admin)", duration_sec=3.5)
    
    # 2. FRONTEND SCENES
    f1 = make_scene_motion('screenshots/01_frontend_homepage_hero.png', 'FRONTEND DISCOVERY', '1. Homepage Experience & Hero Carousel', 'Responsive banner, instant search bar, and popular keyword pills.', 5.0, 'zoom', ((600, 200), (900, 380), 0.5))
    f2 = make_scene_motion('screenshots/02_frontend_homepage_products_grid.png', 'FRONTEND STORE', '2. Fixed Product Grid & Deals', 'Verified CSS: 4-column desktop & 2-column mobile grid with zero shift.', 5.5, 'pan_down', ((500, 400), (550, 750), 0.6))
    f3 = make_scene_motion('screenshots/03_frontend_homepage_reviews.png', 'CUSTOMER TRUST', '3. Social Proof & Verified Reviews', 'Authentic customer feedback, star ratings, and nationwide guarantee.', 4.5, 'zoom')
    f4 = make_scene_motion('screenshots/05_frontend_shop_catalog.png', 'PRODUCT CATALOG', '4. Dynamic Shop Filters & Sorting', 'Category accordions, live price range, and multi-column catalog.', 5.5, 'pan_right', ((200, 450), (450, 300), 0.4))
    f5 = make_scene_motion('screenshots/06_frontend_product_details.png', 'PRODUCT DETAILS', '5. Rich Product Page & Specifications', 'Multi-angle gallery, Bengali descriptions, and low-stock urgency alerts.', 5.5, 'zoom', ((700, 600), (1050, 780), 0.7))
    f6 = make_scene_motion('screenshots/08_frontend_cart_page.png', 'SHOPPING CART', '6. Instant Cart & Order Calculation', 'Item quantities, subtotal calculation, and direct coupon validation.', 5.0, 'zoom', ((400, 500), (1100, 600), 0.6))
    f7 = make_scene_motion('screenshots/09_frontend_checkout_form.png', 'FAST CHECKOUT', '7. Frictionless One-Page Checkout', 'Customer shipping details, city selector, and Cash on Delivery.', 5.5, 'pan_down', ((400, 300), (450, 650), 0.5))
    f8 = make_scene_motion('screenshots/10_frontend_order_tracking.png', 'ORDER TRACKING', '8. 24/7 Self-Service Tracking Portal', 'Real-time order lookup by phone number or unique tracking reference.', 4.0, 'zoom')
    
    # 3. ADMIN SCENES
    a1 = make_scene_motion('screenshots/16_admin_dashboard_overview.png', 'ADMIN CONTROL', '9. Real-Time Analytics Dashboard', 'Gross revenue, order volume, active customer metrics, and sales charts.', 5.5, 'zoom', ((300, 300), (800, 450), 0.5))
    a2 = make_scene_motion('screenshots/17_admin_products_catalog.png', 'ADMIN CATALOG', '10. Product Catalog Directory', 'Active catalog management, price updates, and instant status switches.', 5.0, 'pan_down')
    a3 = make_scene_motion('screenshots/18_admin_create_product_general.png', 'PRODUCT BUILDER', '11. Product Creation & Rich Text Editor', 'Auto-slug generation, category mapping, and sanitized WYSIWYG editor.', 5.5, 'pan_down', ((500, 350), (600, 550), 0.5))
    a4 = make_scene_motion('screenshots/22_admin_orders_management.png', 'ORDER PIPELINE', '12. Order Processing & Fulfillment', 'Multi-state queue: Pending, Processing, Shipped, and Delivered.', 5.0, 'zoom')
    a5 = make_scene_motion('screenshots/32_admin_activity_monitor.png', 'SECURITY AUDIT', '13. Super Admin Activity & Audit Log', 'Real-time monitoring of administrative events, logins, and audit logs.', 5.5, 'zoom', ((400, 350), (850, 450), 0.5))
    
    # 4. MOBILE SCENE
    m1 = make_scene_motion('screenshots/33_mobile_homepage.png', 'MOBILE OPTIMIZED', '14. 100% Mobile Responsive Architecture', 'Touch-optimized mobile shopping experience with fast LCP.', 4.5, 'zoom')
    
    # 5. OUTRO
    outro_full = make_title_card("MAMA BAZAR", "Fast, Reliable & Modern E-Commerce Architecture", badge_text="EXPLORE TODAY", duration_sec=3.5)
    
    # FULL VIDEO (~75s)
    full_scenes = [intro_full, f1, f2, f3, f4, f5, f6, f7, f8, a1, a2, a3, a4, a5, m1, outro_full]
    compile_video(full_scenes, 'full-product-demo.mp4', music)
    
    # FRONTEND ONLY (~42s)
    intro_front = make_title_card("STOREFRONT TOUR", "Complete Customer Shopping & Checkout Experience", badge_text="CUSTOMER JOURNEY", duration_sec=3.0)
    outro_front = make_title_card("MAMA BAZAR", "Seamless Shopping Experience across All Devices", badge_text="SHOP TODAY", duration_sec=3.0)
    front_scenes = [intro_front, f1, f2, f3, f4, f5, f6, f7, f8, m1, outro_front]
    compile_video(front_scenes, 'customer-frontend.mp4', music)
    
    # ADMIN ONLY (~32s)
    intro_admin = make_title_card("ADMIN CONTROL", "Comprehensive Administration & Management Tour", badge_text="ADMIN PANEL", duration_sec=3.0)
    outro_admin = make_title_card("MAMA BAZAR ADMIN", "Complete Control, Analytics & Enterprise Audit", badge_text="ADMINISTRATION", duration_sec=3.0)
    admin_scenes = [intro_admin, a1, a2, a3, a4, a5, outro_admin]
    compile_video(admin_scenes, 'admin-panel.mp4', music)
    
    # Copy to public/ and artifacts/
    import shutil
    art_dir = '/Users/mohammadalinayeem/.gemini/antigravity-ide/brain/99064a10-b3c3-4279-a0e6-3195411d0c9a'
    for v in ['full-product-demo.mp4', 'customer-frontend.mp4', 'admin-panel.mp4']:
        shutil.copy(v, os.path.join('public', v))
        shutil.copy(v, os.path.join(art_dir, v))
        print(f"Copied {v} to public/ and artifacts/")

if __name__ == '__main__':
    main()
