"""Product images for the 6-vial kits, the Build Your Bundle products and the
two remaining collections, built from the batch-labelled product photos
(front label + back vial with the batch number, or "TESTING / RESULTS SOON").

Usage: python3 build_products.py <source_dir> <out_dir>
<source_dir> holds the *-front-back-*.png product photos (and the KLOW photo
made by make_klow.py).
"""
import sys
from pathlib import Path
from PIL import Image, ImageDraw, ImageFilter, ImageFont

SRC, OUT = Path(sys.argv[1]), Path(sys.argv[2])
OUT.mkdir(parents=True, exist_ok=True)
FONT = Path(__file__).with_name("fonts") / "Oswald.ttf"
WHITE, PINK = (245, 242, 243), (222, 13, 246)

P = {
    "reta5": "retatrutide-5mg-front-back-batch-P260628-L013.png",
    "reta20": "retatrutide-20mg-front-back-testing-results-soon.png",
    "tirz10": "tirzepatide-10mg-front-back-batch20260609.png",
    "sema5": "semaglutide-5mg-front-back-batch-SGX-OL-2026-0811-S88.png",
    "cagri5": "cagrilintide-5mg-front-back-testing-results-soon.png",
    "nad500": "nad-500mg-front-back-batch-AYK20260418-NAD500.png",
    "selank5": "selank-5mg-front-back-batch-OP-20260720-B001.png",
    "ghk50": "ghk-cu-50mg-front-back-batch-SGX-OL-2026-0811-K99.png",
    "bpc5": "bpc-157-5mg-front-back-testing-results-soon.png",
    "motsc10": "mots-c-10mg-front-back-testing-results-soon.png",
}
CROP = (215, 85, 1115, 1195)  # front + back vial pair


def font(size, weight="Bold"):
    f = ImageFont.truetype(str(FONT), size)
    f.set_variation_by_name(weight)
    return f


def background(size):
    """Stretch the empty left margin of a product photo into a canvas."""
    src = Image.open(SRC / P["nad500"]).convert("RGB")
    strip = src.crop((0, 0, 150, 1254)).filter(ImageFilter.GaussianBlur(20))
    return strip.crop((30, 0, 120, 1254)).resize(size, Image.LANCZOS)


def feather_mask(size, edge):
    w, h = size
    m = Image.new("L", size, 0)
    ImageDraw.Draw(m).rectangle((edge, edge, w - edge, h - edge), fill=255)
    return m.filter(ImageFilter.GaussianBlur(edge / 2))


def header(canvas, lines, top):
    """Centered lines of (text, colour) runs, letter-spaced like the labels."""
    d = ImageDraw.Draw(canvas)
    y = top
    for runs, size, track in lines:
        f = font(size)
        ascent = f.getmetrics()[0]
        widths = [sum(d.textlength(ch, font=f) + track for ch in t) for t, _ in runs]
        x = (canvas.width - (sum(widths) - track)) / 2
        for (t, c), w in zip(runs, widths):
            for ch in t:
                d.text((x, y + ascent), ch, font=f, fill=c, anchor="ls")
                x += d.textlength(ch, font=f) + track
        y += int(size * 1.25)
    return y


def grid(canvas, keys, cols, box):
    """Place product pairs in a grid inside box=(x0, y0, x1, y1)."""
    x0, y0, x1, y1 = box
    rows = (len(keys) + cols - 1) // cols
    cw, ch = CROP[2] - CROP[0], CROP[3] - CROP[1]
    scale = min((x1 - x0) / (cols * cw), (y1 - y0) / (rows * ch))
    tw, th = int(cw * scale), int(ch * scale)
    mask = feather_mask((tw, th), max(6, int(tw * 0.05)))
    gy = y0 + ((y1 - y0) - rows * th) // 2
    for i, k in enumerate(keys):
        r, c = divmod(i, cols)
        n = min(cols, len(keys) - r * cols)
        gx = x0 + ((x1 - x0) - n * tw) // 2
        tile = Image.open(SRC / P[k]).convert("RGB").crop(CROP).resize((tw, th), Image.LANCZOS)
        canvas.paste(tile, (gx + c * tw, gy + r * th), mask)


def save(canvas, name):
    canvas.save(OUT / f"{name}.webp", quality=82, method=6)
    print(name, canvas.size)


def kit(key, name):
    """One product photo with a "6-VIAL RESEARCH KIT" heading."""
    W = H = 1254
    canvas = background((W, H))
    bottom = header(canvas, [([("6-VIAL ", PINK), ("RESEARCH KIT", WHITE)], 76, 6)], 46)
    photo = Image.open(SRC / P[key]).convert("RGB")
    s = (H - bottom + 30) / H
    pw, ph = int(W * s), int(H * s)
    photo = photo.resize((pw, ph), Image.LANCZOS)
    canvas.paste(photo, ((W - pw) // 2, H - ph), feather_mask((pw, ph), 40))
    save(canvas, name)


def bundle(keys, n, name, cols):
    W = H = 1254
    canvas = background((W, H))
    bottom = header(canvas, [
        ([("BUILD YOUR RESEARCH BUNDLE", WHITE)], 58, 4),
        ([(f"{n} VIALS", PINK)], 50, 6),
    ], 40)
    grid(canvas, keys, cols, (20, bottom + 10, W - 20, H - 20))
    save(canvas, name)


def collection(keys, name):
    canvas = background((1600, 1000))
    grid(canvas, keys, len(keys), (40, 30, 1560, 970))
    save(canvas, name)


kit("cagri5", "cagrilintide-5mg-6-vial-kit-20261010")
kit("ghk50", "ghk-cu-50mg-6-vial-kit-20261010")
kit("nad500", "nad-500mg-6-vial-kit-20261010")
kit("reta20", "retatrutide-20mg-6-vial-kit-20261010")
kit("reta5", "retatrutide-5mg-6-vial-kit-20261010")
kit("selank5", "selank-5mg-6-vial-kit-20261010")
kit("sema5", "semaglutide-5mg-6-vial-kit-20261010")
kit("tirz10", "tirzepatide-10mg-6-vial-kit-20261010")

bundle(["reta5", "selank5", "nad500"], 3, "build-your-bundle-3-vials-20261010", 2)
bundle(["reta5", "tirz10", "sema5", "nad500", "selank5", "ghk50"], 6, "build-your-bundle-6-vials-20261010", 3)
bundle(["reta5", "tirz10", "sema5", "nad500", "selank5", "ghk50", "cagri5", "bpc5", "motsc10"], 9,
       "build-your-bundle-9-vials-20261010", 3)

# Same products the old collection images showed / the description lists.
collection(["sema5", "cagri5"], "comparative-pathway-collection-20261010")
collection(["ghk50", "bpc5"], "telomere-biology-collection-20261010")
