"""Rebuild the bundle hero and research-collection images from the
product photos (front label + batch/lot or "testing" back vial).

Usage: python3 build_images.py <source_dir> <out_dir>
<source_dir> holds the full-size *-front-back-*.png product images.
"""
import sys
from pathlib import Path
from PIL import Image, ImageFilter, ImageDraw

SRC, OUT = Path(sys.argv[1]), Path(sys.argv[2])
OUT.mkdir(parents=True, exist_ok=True)

P = {
    "reta5": "retatrutide-5mg-front-back-batch-P260628-L013.png",
    "reta20": "retatrutide-20mg-front-back-testing-results-soon.png",
    "tirz10": "tirzepatide-10mg-front-back-batch20260609.png",
    "sema5": "semaglutide-5mg-front-back-batch-SGX-OL-2026-0811-S88.png",
    "cagri5": "cagrilintide-5mg-front-back-testing-results-soon.png",
    "nad500": "nad-500mg-front-back-batch-AYK20260418-NAD500.png",
    "selank5": "selank-5mg-front-back-batch-OP-20260720-B001.png",
    "ghk50": "ghk-cu-50mg-front-back-batch-SGX-OL-2026-0811-K99.png",
}

# Crop around the front + back vial pair, leaving some background to feather.
CROP = (215, 85, 1115, 1195)


def background(size):
    """Stretch the empty left margin of a product photo into a canvas."""
    src = Image.open(SRC / P["nad500"]).convert("RGB")
    strip = src.crop((0, 0, 150, 1254)).filter(ImageFilter.GaussianBlur(20))
    strip = strip.crop((30, 0, 120, 1254))
    return strip.resize(size, Image.LANCZOS)


def feather_mask(size, edge):
    w, h = size
    m = Image.new("L", size, 0)
    d = ImageDraw.Draw(m)
    d.rectangle((edge, edge, w - edge, h - edge), fill=255)
    return m.filter(ImageFilter.GaussianBlur(edge / 2))


def compose(keys, size, name, height_frac=0.97, overlap=0.0):
    W, H = size
    canvas = background(size)
    cw, ch = CROP[2] - CROP[0], CROP[3] - CROP[1]
    n = len(keys)
    # Largest scale that fits both the height and the width (tiles overlap a little).
    scale = min(H * height_frac / ch, W / (cw * (n - (n - 1) * overlap)))
    tw, th = int(cw * scale), int(ch * scale)
    step = tw * (1 - overlap)
    total = tw + step * (n - 1)
    x0 = (W - total) / 2
    # Sit the tiles on the canvas floor line (lower edge of the photos).
    y = int((H - th) * 0.62)
    mask = feather_mask((tw, th), max(8, int(tw * 0.06)))
    for i, k in enumerate(keys):
        tile = Image.open(SRC / P[k]).convert("RGB").crop(CROP).resize((tw, th), Image.LANCZOS)
        canvas.paste(tile, (int(x0 + i * step), y), mask)
    canvas.save(OUT / f"{name}.png", optimize=True)
    canvas.save(OUT / f"{name}.webp", quality=88, method=6)
    print(name, size, keys)


compose(["reta5", "selank5", "cagri5"], (2000, 800), "BUNDLE-BUILDER-HERO")
compose(["reta5", "tirz10", "sema5", "cagri5"], (1600, 1000), "OP-RESEARCH-COLLECTION-METABOLIC")
compose(["nad500", "ghk50", "selank5", "cagri5"], (1600, 1000), "OP-RESEARCH-COLLECTION-CELLULAR")
compose(["selank5", "nad500", "ghk50"], (1600, 1000), "OP-RESEARCH-COLLECTION-NEURO")
compose(["ghk50", "nad500", "selank5"], (1600, 1000), "OP-RESEARCH-COLLECTION-REGEN")
