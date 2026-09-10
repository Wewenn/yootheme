# -*- coding: utf-8 -*-
"""Balayage des définitions de wf-yoo-elements.

Les mêmes contrôles que ceux qui avaient trouvé le défaut container:true,
plus ceux des règles de la maison (icon/iconSmall, items sans element:true,
keyframes hors champ CSS, wf_uid, && dans le JS en ligne).

    python3 audit-elements.py /chemin/vers/wf-yoo-elements-sans-wc

Ce contrôle mérite sa place dans _devtest/tools/ : c'est la troisième fois que
le défaut container:true passe en production.
"""
import sys
import json
import os
import re
from collections import Counter, defaultdict

RACINE = sys.argv[1] if len(sys.argv) > 1 else '.'
EL = RACINE + '/modules/element'

defs = {}
for d in sorted(os.listdir(EL)):
    j = os.path.join(EL, d, 'element.json')
    if not os.path.isfile(j):
        continue
    try:
        defs[d] = json.load(open(j, encoding='utf-8'))
    except Exception as e:
        print('JSON INVALIDE  %-30s %s' % (d, e))

print('définitions lues : %d' % len(defs))
tpl = {}
for d, j in defs.items():
    p = os.path.join(EL, d, 'template.php')
    tpl[d] = open(p, encoding='utf-8', errors='replace').read() if os.path.isfile(p) else None

def bloc(titre):
    print('\n══ %s ══' % titre)

# 1. noms internes en double
bloc('noms internes en double')
c = Counter(j.get('name', '?') for j in defs.values())
dup = {k: v for k, v in c.items() if v > 1}
if dup:
    for k, v in dup.items():
        print('  %s × %d  →  %s' % (k, v, [d for d, j in defs.items() if j.get('name') == k]))
else:
    print('  aucun')

# 2. icon / iconSmall
bloc('icon / iconSmall manquants')
manque = [(d, 'icon' not in j, 'iconSmall' not in j) for d, j in defs.items() if 'icon' not in j or 'iconSmall' not in j]
if manque:
    for d, a, b in manque:
        print('  %-32s icon:%s iconSmall:%s' % (d, 'MANQUE' if a else 'ok', 'MANQUE' if b else 'ok'))
else:
    print('  aucun')

# 3. gabarit absent / icône absente
bloc('gabarit ou icône absents')
ko = []
for d, j in defs.items():
    if tpl[d] is None:
        ko.append('%-32s template.php absent' % d)
    ic = os.path.join(EL, d, 'images', 'ic30.svg')
    if not os.path.isfile(ic):
        ko.append('%-32s images/ic30.svg absent' % d)
print('\n'.join('  ' + x for x in ko) if ko else '  aucun')

# 4. LE défaut : lit $children sans container:true
bloc('lit $children sans container:true')
ko = []
for d, j in defs.items():
    t = tpl[d] or ''
    lit = bool(re.search(r'\$children|\$builder->render|\$props\[.children', t))
    if lit and not j.get('container'):
        ko.append('  %-32s element=%s container=%s' % (d, j.get('element'), j.get('container')))
print('\n'.join(ko) if ko else '  aucun')

# 5. l'inverse : container:true mais ne lit jamais d'enfants
bloc('container:true sans usage des enfants')
ko = []
for d, j in defs.items():
    t = tpl[d] or ''
    if j.get('container') and not re.search(r'\$children|\$builder->render|fragment', t) and not j.get('fragment'):
        ko.append('  ' + d)
print('\n'.join(ko) if ko else '  aucun')

# 6. items qui déclarent element:true
bloc('items déclarant element:true')
ko = []
for d, j in defs.items():
    if d.endswith('-item') and j.get('element'):
        ko.append('  %-32s %s' % (d, j.get('name')))
print('\n'.join(ko) if ko else '  aucun')

# 7. titres d'items en double
bloc("titres d'items en double")
ti = defaultdict(list)
for d, j in defs.items():
    if d.endswith('-item') or not j.get('element'):
        ti[str(j.get('title', '?'))].append(d)
dup = {k: v for k, v in ti.items() if len(v) > 1}
if dup:
    for k in sorted(dup, key=lambda k: -len(dup[k])):
        print('  « %s » × %d : %s' % (k, len(dup[k]), ', '.join(sorted(dup[k]))))
else:
    print('  aucun')

# 8. entités mal encodées dans les libellés
bloc('entités HTML dans les titres / groupes')
ko = []
for d, j in defs.items():
    for cle in ('title', 'group'):
        v = str(j.get(cle, ''))
        if '&#' in v or '&amp;' in v:
            ko.append('  %-32s %s = %s' % (d, cle, v))
print('\n'.join(ko) if ko else '  aucun')

# 9. keyframes dans un champ CSS ou un <style> de gabarit
bloc('@keyframes dans un gabarit (préfixeur YOOtheme)')
ko = []
for d in defs:
    t = tpl[d] or ''
    if '@keyframes' in t:
        ko.append('  %-32s %d occurrence(s)' % (d, t.count('@keyframes')))
print('\n'.join(ko) if ko else '  aucun')

# 10. wp_unique_id au lieu de wf_uid
bloc('wp_unique_id au lieu de wf_uid')
ko = [d for d in defs if 'wp_unique_id' in (tpl[d] or '')]
print('\n'.join('  ' + d for d in ko) if ko else '  aucun')

# 11. identifiants DOM sans uid (risque de doublon si l'élément est posé deux fois)
bloc('id= en dur sans wf_uid dans le gabarit')
ko = []
for d in defs:
    t = tpl[d] or ''
    if re.search(r'id="[a-z][a-z0-9_-]{2,}"', t) and 'wf_uid' not in t:
        ko.append('  ' + d)
print('\n'.join(ko) if ko else '  aucun')

# 12. && dans du JS en ligne (attribut on*)
bloc('&& dans un attribut on… (échappement HTML)')
ko = []
for d in defs:
    t = tpl[d] or ''
    for m in re.finditer(r'on(click|change|input|keydown|mouseover)="([^"]{0,400})"', t):
        if '&&' in m.group(2):
            ko.append('  %-32s on%s' % (d, m.group(1)))
print('\n'.join(ko) if ko else '  aucun')

# 13. appel réseau externe depuis un gabarit
bloc('URL externe dans un gabarit')
ko = []
for d in defs:
    t = tpl[d] or ''
    for m in set(re.findall(r'https?://[a-z0-9.\-]+', t)):
        if not re.search(r'(w3\.org|we-frame\.fr|schema\.org|localhost)', m):
            ko.append('  %-32s %s' % (d, m))
print('\n'.join(sorted(set(ko))) if ko else '  aucun')

# 14. champs déclarés mais absents du fieldset
bloc('champs déclarés jamais exposés dans le panneau')
NATIFS = {'id', 'class', 'css', 'attributes', 'animation', 'margin', 'maxwidth', 'position',
          'transform', 'visibility', 'blend', 'status', 'source', 'name', 'content'}
ko = []
for d, j in defs.items():
    champs = set((j.get('fields') or {}).keys())
    if not champs:
        continue
    plat = json.dumps(j.get('fieldset') or {}, ensure_ascii=False)
    orphelins = sorted(x for x in champs - NATIFS if '"%s"' % x not in plat)
    if orphelins:
        ko.append('  %-32s %s' % (d, ', '.join(orphelins)))
print('\n'.join(ko) if ko else '  aucun')

# 15. groupes du panneau
bloc('groupes du panneau')
g = Counter(str(j.get('group', '(aucun)')) for j in defs.values())
for k, v in sorted(g.items(), key=lambda x: -x[1]):
    print('  %3d  %s' % (v, k))
