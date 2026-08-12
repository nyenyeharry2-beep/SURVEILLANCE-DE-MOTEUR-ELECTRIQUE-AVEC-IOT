# ÉQUATIONS ET TRAITEMENT DU SIGNAL

## 1. Accélération mesurée

L’ADXL345 fournit \(a_x, a_y, a_z\) (m/s² via la lib Adafruit).

Après acquisition de \(N\) échantillons, on retire la composante continue (gravité + biais) :

\[
a_{x,ac}(i) = a_x(i) - \bar{a}_x,\quad
\bar{a}_x = \frac{1}{N}\sum_{i=1}^{N} a_x(i)
\]

(idem pour \(y\) et \(z\)).

**Différence conceptuelle**

| Grandeur | Signification |
|----------|----------------|
| Accélération | Variation de vitesse dans le temps (m/s² ou g). |
| Accélération RMS | Énergie vibratoire moyenne quadratique en accélération. |
| Vitesse vibratoire RMS (mm/s) | Indicateur courant en maintenance (ISO 10816) — **nécessite** une vitesse, pas une accel brute. |

## 2. Accélération RMS (grandeur fiable du projet)

\[
A_{RMS} = \sqrt{\frac{1}{N}\sum_{i=1}^{N}\left(a_{x,ac}(i)^2 + a_{y,ac}(i)^2 + a_{z,ac}(i)^2\right)}
\]

Symboles : \(N\) nombre d’échantillons ; \(a_{*,ac}\) accélérations dynamiques (m/s²).

## 3. Estimation de la vitesse vibratoire RMS

Intégration trapézoïdale (par axe) :

\[
v_x(k) = v_x(k-1) + \frac{a_{x,ac}(k-1)+a_{x,ac}(k)}{2}\,\Delta t
\]

puis

\[
V_{RMS} = \sqrt{\frac{1}{N-1}\sum_{k=2}^{N} \lVert \mathbf{v}(k)\rVert^2}
\quad\text{(m/s)},\qquad
V_{RMS,mm/s} = 1000\,V_{RMS}
\]

### Hypothèses et limites (honnêteté scientifique)

- Le retrait de moyenne approxime un filtre passe-haut ; une dérive résiduelle fausse \(v\).
- Bande passante / bruit / calibration de l’ADXL345 ≠ vibromètre industriel.
- Sans FFT, on n’applique pas proprement \(V = A/(2\pi f)\) (il faudrait la fréquence dominante).
- **Conclusion** : afficher \(V_{RMS}\) comme **estimation** ; fonder l’interprétation robuste sur \(A_{RMS}\) + calibration relative (baseline machine saine).

### Solution réaliste pour du mm/s traçable

Utiliser un **accéléromètre IEPE** + conditionneur, ou un **vibromètre** affichant déjà mm/s RMS, et comparer / remplacer l’estimation ADXL345.

## 4. Vitesse de rotation (capteur IR)

Avec \(P\) impulsions par tour (\(P=1\) si une seule marque optique) sur une durée \(\Delta t\) :

\[
RPM = \frac{N_{impulsions}}{P}\times\frac{60000}{\Delta t_{ms}}
\]

Anti-rebond : ignorer tout front si \(\Delta t_{impulsion} < 3000\,\mu s\).
Le capteur IR détecte une variation de réflexion (marque claire/foncée) — pas besoin d’aimant.

## 5. Erreurs de validation

\[
E = \lvert X_{mesuré} - X_{réel}\rvert
\]

\[
E_r = \frac{E}{\lvert X_{réel}\rvert}
\]

\[
E_{\%} = E_r \times 100
\]

Précision expérimentale (exemple) : moyenne de \(E_{\%}\) sur \(n\) essais, écart-type.

## 6. Relation sinusoïdale (rappel théorique)

Pour une vibration harmonique pure de pulsation \(\omega=2\pi f\) :

\[
v_{RMS} = \frac{a_{RMS}}{\omega}
\]

Non utilisée seule dans le firmware faute de \(f\) connue en ligne ; fournie pour le mémoire.
