/**
 * Sidebar FALCify – Gutenberg (maquette)
 */
/* global falcifyEditorVars */
import { registerPlugin } from '@wordpress/plugins';
import { PluginSidebar } from '@wordpress/edit-post';
import { useSelect, useDispatch } from '@wordpress/data';
import { store as editorStore } from '@wordpress/editor';
import {
	Button, PanelBody, Spinner, TextareaControl, Notice,
	__experimentalText as Text, __experimentalHStack as HStack,
} from '@wordpress/components';
import { useEffect, useMemo, useState, useCallback } from '@wordpress/element';
import { count as wordCount } from '@wordpress/wordcount';
import apiFetch from '@wordpress/api-fetch';

const ProgressBar = ({ value = 0, max = 100 }) => {
	const pct = Math.min(100, Math.round((value / max) * 100));
	return (
		<div className="falcify-progress">
			<div className="falcify-progress__bar" style={{ width: `${pct}%` }} />
		</div>
	);
};

const formatWords = (n) => new Intl.NumberFormat('fr-FR').format(n);

const Sidebar = () => {
	const postContent = useSelect((s) => s(editorStore).getEditedPostAttribute('content'), []);
	const postId      = useSelect((s) => s(editorStore).getCurrentPostId(), []);
	const { editPost } = useDispatch(editorStore);

	const [status, setStatus] = useState({ plan: 'Gratuit', limit: 500, used: 0 });
	const [loadingStatus, setLoadingStatus] = useState(true);
	const [generated, setGenerated] = useState('');
	const [score, setScore] = useState(null);
	const [busy, setBusy] = useState(false);
	const [error, setError] = useState('');

	const wordsInPost = useMemo(() => {
		const plain = (postContent || '').replace(/<[^>]*>/g, ' ');
		return wordCount(plain, 'words', {});
	}, [postContent]);

	const refreshStatus = useCallback(async () => {
		try {
			setLoadingStatus(true);
			const res = await apiFetch({ path: '/falcify/v1/status', method: 'GET' });
			setStatus(res);
			setError('');
		} catch {
			setError("Impossible de récupérer le quota.");
		} finally {
			setLoadingStatus(false);
		}
	}, []);

	useEffect(() => { refreshStatus(); }, [refreshStatus]);

	const handleGenerate = async () => {
		setBusy(true); setError(''); setScore(null);
		try {
			const res = await apiFetch({
				path: '/falcify/v1/generate',
				method: 'POST',
				data: { post_id: postId, html: postContent || '', lang: 'fr', require_score: true },
			});
			const { text_falc, score_falc } = res || {};
			setGenerated(text_falc || '');
			if (typeof score_falc === 'number') setScore(score_falc);
			editPost({ meta: { _falcify_falc: text_falc || '' } });
			refreshStatus();
		} catch (e) {
			setError(e?.message || 'Erreur pendant la génération.');
		} finally {
			setBusy(false);
		}
	};

	const remaining = Math.max(0, (status.limit || 0) - (status.used || 0));

	return (
		<PluginSidebar name="falcify-sidebar" title="FALCify" icon="universal-access-alt">
			<div className="falcify-side">
				<div className="falcify-card">
					<div className="falcify-card__header">
						<img src={falcifyEditorVars.logo} alt="FALCify" className="falcify-logo" width="28" height="28" />
						<div className="falcify-title">FALCify</div>
					</div>

					<Text className="falcify-muted">
						Générez une version Facile à Lire et à Comprendre (FALC) de votre contenu.
					</Text>

					<div className="falcify-sub-card">
						<div className="falcify-sub-card__plan">
							{loadingStatus ? (
								<Spinner />
							) : (
								<>
									<div className="falcify-badge">ABONNEMENT {status.plan.toUpperCase()}</div>
									<ProgressBar value={status.used} max={status.limit} />
									<div className="falcify-counter">
										{formatWords(status.used)} / {formatWords(status.limit)} mots
									</div>
								</>
							)}
						</div>
						<Button className="falcify-btn-premium" variant="secondary"
							onClick={() => window.open(falcifyEditorVars.upgradeUrl, '_blank', 'noopener')}>
							PASSER EN PREMIUM
						</Button>
					</div>

					<div className="falcify-actions">
						<Button variant="primary" onClick={handleGenerate} disabled={busy || remaining <= 0}>
							{busy ? 'Génération…' : 'Générer mon contenu en FALC'}
						</Button>
						{remaining <= 0 && <Notice status="warning" isDismissible={false}>Quota atteint. Passez en Premium.</Notice>}
					</div>

					<PanelBody title="TEXTE généré" initialOpen={true}>
						{busy && <Spinner />}
						<TextareaControl value={generated} onChange={setGenerated} rows={8}
							help="Vous pouvez ajuster avant d’enregistrer." placeholder="Le texte en version FALC apparaîtra ici."/>
						<HStack alignment="left" spacing={8}>
							{score !== null && <Text className="falcify-score">Auto‑évaluation : <strong>{Math.round(score)}% conforme</strong>*</Text>}
							<Text className="falcify-score">{wordsInPost > 0 && <>Texte actuel : {formatWords(wordsInPost)} mots</>}</Text>
						</HStack>
						<Text className="falcify-footnote">* minimum de 80 % ; régénérez si besoin.</Text>
						{error && <Notice status="error" onRemove={() => setError('')}>{error}</Notice>}
					</PanelBody>
				</div>
			</div>
		</PluginSidebar>
	);
};

registerPlugin('falcify-panel', { render: Sidebar });
