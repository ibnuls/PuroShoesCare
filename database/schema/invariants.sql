CREATE OR REPLACE FUNCTION puro_immutable() RETURNS trigger LANGUAGE plpgsql AS $$ BEGIN RAISE EXCEPTION 'Historical rows are immutable; create a new version'; END $$;
CREATE TRIGGER tariff_immutable BEFORE UPDATE OR DELETE ON service_tariff_versions FOR EACH ROW EXECUTE FUNCTION puro_immutable();
CREATE TRIGGER promo_version_immutable BEFORE UPDATE OR DELETE ON promotion_versions FOR EACH ROW EXECUTE FUNCTION puro_immutable();
CREATE TRIGGER shipping_immutable BEFORE UPDATE OR DELETE ON shipping_policy_versions FOR EACH ROW EXECUTE FUNCTION puro_immutable();
CREATE TRIGGER qris_version_immutable BEFORE UPDATE OR DELETE ON qris_account_versions FOR EACH ROW EXECUTE FUNCTION puro_immutable();
CREATE TRIGGER quote_lines_immutable BEFORE UPDATE OR DELETE ON quote_items FOR EACH ROW EXECUTE FUNCTION puro_immutable();
CREATE TRIGGER verified_immutable BEFORE UPDATE OR DELETE ON verified_payments FOR EACH ROW EXECUTE FUNCTION puro_immutable();
CREATE TRIGGER history_immutable BEFORE UPDATE OR DELETE ON order_status_history FOR EACH ROW EXECUTE FUNCTION puro_immutable();
CREATE TRIGGER qc_immutable BEFORE UPDATE OR DELETE ON qc_checks FOR EACH ROW EXECUTE FUNCTION puro_immutable();
CREATE OR REPLACE FUNCTION puro_quote_item_owner() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF (SELECT order_id FROM price_quotes WHERE id=NEW.quote_id) IS DISTINCT FROM (SELECT order_id FROM order_items WHERE id=NEW.order_item_id) THEN RAISE EXCEPTION 'Quote item belongs to another order'; END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER quote_item_owner BEFORE INSERT ON quote_items FOR EACH ROW EXECUTE FUNCTION puro_quote_item_owner();
CREATE OR REPLACE FUNCTION puro_submission_owner() RETURNS trigger LANGUAGE plpgsql AS $$
BEGIN
 IF (SELECT order_id FROM price_quotes WHERE id=NEW.quote_id) IS DISTINCT FROM (SELECT order_id FROM payment_references WHERE id=NEW.reference_id) THEN RAISE EXCEPTION 'Payment reference belongs to another order'; END IF;
 IF TG_OP='UPDATE' AND (OLD.decision<>'PENDING' OR NEW.quote_id<>OLD.quote_id OR NEW.reference_id<>OLD.reference_id OR NEW.proof_media_id<>OLD.proof_media_id OR NEW.claimed_amount<>OLD.claimed_amount OR NEW.attempt_no<>OLD.attempt_no OR NEW.request_key<>OLD.request_key) THEN RAISE EXCEPTION 'Payment evidence and final decisions cannot be overwritten'; END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER submission_owner BEFORE INSERT OR UPDATE ON payment_submissions FOR EACH ROW EXECUTE FUNCTION puro_submission_owner();
CREATE OR REPLACE FUNCTION puro_verified_owner() RETURNS trigger LANGUAGE plpgsql AS $$
DECLARE s payment_submissions%ROWTYPE; q price_quotes%ROWTYPE; due numeric;
BEGIN
 SELECT * INTO s FROM payment_submissions WHERE id=NEW.submission_id;
 SELECT * INTO q FROM price_quotes WHERE id=s.quote_id;
 SELECT total INTO due FROM quote_totals WHERE quote_id=q.id;
 IF q.order_id<>NEW.order_id OR s.decision<>'VERIFIED' OR q.decision<>'ACCEPTED' OR s.claimed_amount<>due OR q.version<>(SELECT MAX(version) FROM price_quotes WHERE order_id=NEW.order_id) OR s.attempt_no<>(SELECT MAX(attempt_no) FROM payment_submissions WHERE quote_id=q.id) THEN RAISE EXCEPTION 'Verification must match the current accepted quote and latest evidence'; END IF;
 RETURN NEW;
END $$;
CREATE TRIGGER verified_owner BEFORE INSERT ON verified_payments FOR EACH ROW EXECUTE FUNCTION puro_verified_owner();
