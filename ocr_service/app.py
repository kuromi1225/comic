import os
import uuid
import pytesseract
from flask import Flask, request, jsonify
from PIL import Image
import re

# Flaskアプリケーションの初期化
app = Flask(__name__)

# 一時ファイルの保存場所 (コンテナ内)
UPLOAD_FOLDER = '/tmp/ocr_uploads'
os.makedirs(UPLOAD_FOLDER, exist_ok=True) # 存在しなければ作成

# 日本語と英語の言語を指定
TESSERACT_LANG = 'jpn+eng'

# ISBN抽出用の正規表現 (978で始まる13桁)
ISBN_REGEX = r'978[0-9]{10}'

@app.route('/ocr', methods=['POST'])
def perform_ocr():
    """画像ファイルを受け取り、OCRを実行してISBNを抽出するAPIエンドポイント"""
    if 'file' not in request.files:
        return jsonify({"error": "ファイルがリクエストに含まれていません"}), 400

    file = request.files['file']

    if file.filename == '':
        return jsonify({"error": "ファイル名が空です"}), 400

    if file:
        try:
            # 安全なファイル名 (実際には不要かも) & 一時保存パス
            filename = str(uuid.uuid4()) + os.path.splitext(file.filename)[1]
            temp_filepath = os.path.join(UPLOAD_FOLDER, filename)
            file.save(temp_filepath)
            app.logger.info(f"ファイル '{filename}' を一時保存しました: {temp_filepath}")

            # Pillowで画像を開き、pytesseractでOCR実行
            img = Image.open(temp_filepath)
            # configオプションで言語を指定 (環境変数やTesseract設定でも可能)
            # 必要であれば --psm オプションなども追加
            ocr_text = pytesseract.image_to_string(img, lang=TESSERACT_LANG)
            app.logger.info("OCR 処理完了")
            # app.logger.debug(f"OCR抽出テキスト:\n{ocr_text}") # デバッグ用に全文ログ

            # 抽出テキストからISBNを検索
            # 注意: OCR結果は改行などが不安定なことがあるため、テキスト全体から検索
            isbns = re.findall(ISBN_REGEX, ocr_text)

            # 重複を除去
            unique_isbns = sorted(list(set(isbns)))
            app.logger.info(f"抽出されたユニークISBN: {unique_isbns}")

            # 一時ファイルを削除
            os.remove(temp_filepath)
            app.logger.info(f"一時ファイルを削除しました: {temp_filepath}")

            # 結果をJSONで返す
            return jsonify({"isbns": unique_isbns})

        except pytesseract.TesseractNotFoundError:
             app.logger.error("Tesseractが見つかりません。パスを確認してください。")
             # 一時ファイルが存在すれば削除
             if 'temp_filepath' in locals() and os.path.exists(temp_filepath):
                 os.remove(temp_filepath)
             return jsonify({"error": "サーバー内部エラー: Tesseract OCRが見つかりません"}), 500
        except Exception as e:
            app.logger.error(f"OCR処理中に予期せぬエラーが発生: {e}", exc_info=True)
            # 一時ファイルが存在すれば削除
            if 'temp_filepath' in locals() and os.path.exists(temp_filepath):
                os.remove(temp_filepath)
            return jsonify({"error": f"サーバー内部エラーが発生しました: {e}"}), 500
    else:
        return jsonify({"error": "無効なファイル形式の可能性があります"}), 400

if __name__ == '__main__':
    # デバッグモードで実行 (本番環境ではGunicorn等を使用)
    # host='0.0.0.0' でコンテナ外部からもアクセス可能に
    app.run(debug=True, host='0.0.0.0', port=5000)