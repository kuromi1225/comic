# ベースイメージを選択 (Python 3.10 slim)
FROM python:3.10-slim

# 環境変数設定 (Pythonの出力をバッファしないなど)
ENV PYTHONUNBUFFERED 1
ENV PYTHONDONTWRITEBYTECODE 1

# OSパッケージ管理ツールの更新とTesseract OCRのインストール
# 日本語(jpn)と英語(eng)の言語パックもインストール
RUN apt-get update && apt-get install -y --no-install-recommends \
    tesseract-ocr \
    tesseract-ocr-jpn \
    tesseract-ocr-eng \
    # Pillowが必要とする可能性のあるライブラリ
    libjpeg62-turbo \
    # フォント関連 (日本語OCRに役立つ場合がある)
    fonts-ipafont-gothic \
    # 不要になったパッケージキャッシュの削除
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# 作業ディレクトリを設定
WORKDIR /app

# requirements.txt をコピーして依存ライブラリをインストール
COPY requirements.txt .
RUN pip install --no-cache-dir -r requirements.txt

# アプリケーションコードをコピー
COPY app.py .

# Flaskが使用するポートを公開 (コンテナ内部)
EXPOSE 5000

# アプリケーションを実行
# CMD ["python", "app.py"]
# 本番運用に近い形で動かすならGunicorn推奨（別途requirements.txtに追加が必要）
# CMD ["gunicorn", "--bind", "0.0.0.0:5000", "app:app"]
# まずは開発用にpython直接実行
CMD ["python", "-u", "app.py"]