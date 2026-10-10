"""KLOW blend product photo: the KPV + GHK-Cu blend photo with the product
name on the front label replaced by "KLOW BLEND". The back vial keeps
"TESTING / RESULTS SOON" (no KLOW COA is published).

Usage: python3 make_klow.py <source_dir> <out_dir>
"""
import sys
from pathlib import Path
import numpy as np
import cv2
from PIL import Image, ImageDraw, ImageFont

SRC, OUT = Path(sys.argv[1]), Path(sys.argv[2])
FONT = Path(__file__).with_name("fonts") / "Oswald.ttf"

im = Image.open(SRC / "kpv-ghk-cu-blend-front-back-testing-results-soon.png").convert("RGB")
a = np.array(im)

# Name row on the front label.
x0, y0, x1, y1 = 248, 772, 652, 850
region = a[y0:y1, x0:x1]
mag = region.max(axis=2).astype(int)
mask = np.zeros(a.shape[:2], np.uint8)
mask[y0:y1, x0:x1] = (mag > 90).astype(np.uint8) * 255
mask = cv2.dilate(mask, np.ones((7, 7), np.uint8))
# Sample the text colours before painting them out.
pix = region.reshape(-1, 3)
white = tuple(int(v) for v in np.median(pix[(pix.min(axis=1) > 200)], axis=0))
pink = pix[(pix[:, 0] > 170) & (pix[:, 1] < 90) & (pix[:, 2] > 170)]
pink = tuple(int(v) for v in np.median(pink, axis=0))
a = cv2.inpaint(a, mask, 9, cv2.INPAINT_TELEA)

im = Image.fromarray(a)
d = ImageDraw.Draw(im)
font = ImageFont.truetype(str(FONT), 62)
font.set_variation_by_name("Bold")
cx, base = 450, 841  # label centre and text baseline
parts = [("KLOW ", white), ("BLEND", pink)]
total = sum(d.textlength(t, font=font) for t, _ in parts)
x = cx - total / 2
for t, c in parts:
    d.text((x, base), t, font=font, fill=c, anchor="ls")
    x += d.textlength(t, font=font)

OUT.mkdir(parents=True, exist_ok=True)
im.save(OUT / "klow-blend-front-back-testing-results-soon-20261010.webp", quality=82, method=6)
print("white", white, "pink", pink)
