# genere les visuels des spectacles (front/public/img/spectacles)
# un visuel different par spectacle, dans les couleurs de son style
# usage : python3 front/tools/visuels.py
import math
import random
from pathlib import Path

DOSSIER = Path(__file__).resolve().parent.parent / 'public' / 'img' / 'spectacles'
L, H = 800, 500
ROUGE = '#e4322b'

# teinte principale et teinte secondaire de chaque style
STYLES = {
    'classic-rock': (24, 44),
    'blues-rock': (218, 250),
    'metal': (0, 20),
    'chanson': (338, 12),
    'reggae': (130, 50),
    'punk': (305, 335),
    'folk': (38, 80),
    'electro-rock': (188, 260),
}
# nombre de spectacles par style dans le jeu de donnees (3 visuels chacun)
NOMBRE = {'classic-rock': 8, 'metal': 7, 'blues-rock': 6, 'chanson': 4,
          'folk': 3, 'reggae': 3, 'punk': 3, 'electro-rock': 3}


def hsl(h, s, l, a=None):
    if a is None:
        return f'hsl({h % 360:.0f},{s:.0f}%,{l:.0f}%)'
    return f'hsla({h % 360:.0f},{s:.0f}%,{l:.0f}%,{a:.2f})'


def fond(r, h1, h2):
    cx, cy = r.uniform(0.2, 0.8), r.uniform(0.05, 0.45)
    return f'''<defs>
<linearGradient id="f" x1="0" y1="0" x2="0.3" y2="1"><stop offset="0" stop-color="{hsl(h1, 30, 14)}"/><stop offset="1" stop-color="{hsl(h2, 25, 5)}"/></linearGradient>
<radialGradient id="h" cx="{cx:.2f}" cy="{cy:.2f}" r="0.7"><stop offset="0" stop-color="{hsl(h1, 80, 55)}" stop-opacity="0.55"/><stop offset="1" stop-color="{hsl(h1, 80, 30)}" stop-opacity="0"/></radialGradient>
<linearGradient id="b" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#fff" stop-opacity="0.32"/><stop offset="1" stop-color="#fff" stop-opacity="0"/></linearGradient>
<linearGradient id="e" x1="0" y1="1" x2="0" y2="0"><stop offset="0" stop-color="{ROUGE}"/><stop offset="1" stop-color="{hsl(h2, 85, 60)}"/></linearGradient>
</defs>
<rect width="{L}" height="{H}" fill="url(#f)"/><rect width="{L}" height="{H}" fill="url(#h)"/>'''


def faisceaux(r, h1, h2):
    # projecteurs accroches a une poutre
    out = [f'<rect x="0" y="34" width="{L}" height="6" fill="#000" opacity="0.5"/>']
    n = r.randint(4, 7)
    for i in range(n):
        x = (i + 0.5) * L / n + r.uniform(-25, 25)
        cible = x + r.uniform(-260, 260)
        demi = r.uniform(60, 130)
        out.append(f'<polygon points="{x - 6:.0f},40 {x + 6:.0f},40 {cible + demi:.0f},{H} {cible - demi:.0f},{H}" fill="url(#b)" opacity="{r.uniform(0.35, 0.8):.2f}"/>')
        couleur = ROUGE if i == r.randrange(n) else hsl(r.choice([h1, h2]), 90, 70)
        out.append(f'<circle cx="{x:.0f}" cy="40" r="7" fill="{couleur}"/>')
    sol = H - 70
    out.append(f'<rect x="0" y="{sol}" width="{L}" height="70" fill="#000" opacity="0.55"/>')
    out.append(f'<rect x="0" y="{sol}" width="{L}" height="3" fill="{ROUGE}" opacity="0.9"/>')
    return out


def ondes(r, h1, h2):
    # cercles concentriques autour d'un point
    cx, cy = r.uniform(0.25, 0.75) * L, r.uniform(0.35, 0.65) * H
    out = []
    for i in range(14, 0, -1):
        out.append(f'<circle cx="{cx:.0f}" cy="{cy:.0f}" r="{i * 34}" fill="none" stroke="{hsl(h2 if i % 2 else h1, 70, 65)}" stroke-width="{1.5 if i % 3 else 3}" opacity="{0.06 + 0.5 / i:.2f}"/>')
    out.append(f'<circle cx="{cx:.0f}" cy="{cy:.0f}" r="26" fill="{ROUGE}"/>')
    return out


def egaliseur(r, h1, h2):
    # barres d'egaliseur posees en bas
    out = []
    n = r.choice([24, 32, 40])
    largeur = L / n
    phase, freq = r.uniform(0, 6), r.uniform(0.15, 0.35)
    for i in range(n):
        v = 0.55 + 0.3 * math.sin(i * freq + phase) + 0.15 * math.sin(i * freq * 3.1 + phase * 2)
        haut = max(20, v * H * 0.62 + r.uniform(-30, 30))
        x = i * largeur + largeur * 0.18
        out.append(f'<rect x="{x:.1f}" y="{H - 40 - haut:.0f}" width="{largeur * 0.64:.1f}" height="{haut:.0f}" rx="2" fill="url(#e)" opacity="0.9"/>')
        out.append(f'<rect x="{x:.1f}" y="{H - 36}" width="{largeur * 0.64:.1f}" height="{haut * 0.18:.0f}" rx="2" fill="url(#e)" opacity="0.18"/>')
    return out


def signal(r, h1, h2):
    # plusieurs formes d'onde superposees
    out = []
    for k in range(5):
        a1, a2 = r.uniform(30, 90), r.uniform(10, 40)
        f1, f2, p = r.uniform(0.008, 0.02), r.uniform(0.03, 0.06), r.uniform(0, 6)
        y0 = H * 0.5 + r.uniform(-40, 40)
        pts = []
        for x in range(0, L + 1, 8):
            enveloppe = math.sin(math.pi * x / L) ** 1.5
            y = y0 + enveloppe * (a1 * math.sin(x * f1 + p) + a2 * math.sin(x * f2 + p * 1.7))
            pts.append(f'{x},{y:.0f}')
        couleur = ROUGE if k == 0 else hsl(h2 if k % 2 else h1, 80, 68)
        out.append(f'<polyline points="{" ".join(pts)}" fill="none" stroke="{couleur}" stroke-width="{3.5 if k == 0 else 1.6}" opacity="{0.95 if k == 0 else 0.45:.2f}" stroke-linecap="round"/>')
    return out


def vinyle(r, h1, h2):
    # disque en partie hors du cadre
    cx = r.choice([r.uniform(-60, 120), r.uniform(680, 860)])
    cy, rayon = r.uniform(180, 320), r.uniform(230, 290)
    out = [f'<circle cx="{cx:.0f}" cy="{cy:.0f}" r="{rayon:.0f}" fill="#0a090b"/>']
    for g in range(int(rayon) - 12, 70, -9):
        out.append(f'<circle cx="{cx:.0f}" cy="{cy:.0f}" r="{g}" fill="none" stroke="#fff" stroke-width="0.8" opacity="{r.uniform(0.04, 0.1):.2f}"/>')
    out.append(f'<path d="M{cx - rayon * 0.7:.0f},{cy - rayon * 0.7:.0f} A{rayon:.0f},{rayon:.0f} 0 0 1 {cx + rayon * 0.7:.0f},{cy - rayon * 0.7:.0f}" fill="none" stroke="{hsl(h1, 70, 70)}" stroke-width="18" opacity="0.12"/>')
    out.append(f'<circle cx="{cx:.0f}" cy="{cy:.0f}" r="62" fill="{ROUGE}"/>')
    out.append(f'<circle cx="{cx:.0f}" cy="{cy:.0f}" r="40" fill="none" stroke="#fff" stroke-width="1" opacity="0.35"/>')
    out.append(f'<circle cx="{cx:.0f}" cy="{cy:.0f}" r="7" fill="#0a090b"/>')
    return out


def trame(r, h1, h2):
    # trame de points, plus gros pres de la source de lumiere
    cx, cy = r.uniform(0.2, 0.8) * L, r.uniform(0.2, 0.8) * H
    pas = 22
    out = []
    for y in range(pas // 2, H, pas):
        for x in range(pas // 2, L, pas):
            d = math.hypot(x - cx, y - cy) / 520
            rayon = max(0.0, 8.5 * (1 - d))
            if rayon > 0.6:
                couleur = ROUGE if d < 0.18 else hsl(h2 if (x // pas + y // pas) % 5 == 0 else h1, 75, 62)
                out.append(f'<circle cx="{x}" cy="{y}" r="{rayon:.1f}" fill="{couleur}" opacity="{0.35 + 0.6 * (1 - d):.2f}"/>')
    return out


MOTIFS = [faisceaux, egaliseur, ondes, signal, vinyle, trame]


def visuel(slug, k):
    h1, h2 = STYLES[slug]
    r = random.Random(f'{slug}-{k}')
    # spectacle j (0, 1, 2...) : ses 3 visuels ont 3 motifs differents
    j, n = divmod(k - 1, 3)
    decalage = list(STYLES).index(slug)
    motif = MOTIFS[(j * 2 + n * 3 + decalage) % len(MOTIFS)]
    h1, h2 = h1 + r.uniform(-10, 10), h2 + r.uniform(-10, 10)
    corps = '\n'.join(motif(r, h1, h2))
    return f'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 {L} {H}" width="{L}" height="{H}">\n{fond(r, h1, h2)}\n{corps}\n</svg>\n'


if __name__ == '__main__':
    DOSSIER.mkdir(parents=True, exist_ok=True)
    for ancien in DOSSIER.glob('*.svg'):
        ancien.unlink()
    total = 0
    for slug, nombre in NOMBRE.items():
        for k in range(1, nombre * 3 + 1):
            (DOSSIER / f'{slug}-{k}.svg').write_text(visuel(slug, k))
            total += 1
    print(total, 'visuels dans', DOSSIER)
