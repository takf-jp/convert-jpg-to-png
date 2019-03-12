<?php
/**
 * Created by PhpStorm.
 * User: takf
 * Date: 2019-03-04
 * Time: 15:29
 */

$app = new ConvertPicture();

$app->execute();

abstract class App
{
    abstract public function execute();

    /**
     * メッセージをコンソールに表示
     *
     * @param string $message
     */
    protected function showMessage(string $message)
    {
        echo escapeshellcmd($message) . PHP_EOL;
    }

    /**
     * ディレクトリ名を入力させる
     *
     * @param string $message
     * @return string
     */
    protected function inputDirectoryName(string $message): string
    {
        echo $message;
        return trim(fgets(STDIN));
    }

    /**
     * 画像ファイルの変換
     *
     * @param string $file
     * @param $image
     * @return bool
     */
    abstract public function convertImgFile(string $file, $image): bool;

    /**
     * 保存先ディレクトリの権限変更
     *
     * @param string $directory
     * @return bool
     */
    protected function setDirectoryToPermission(string $directory): bool
    {
        return chmod($directory, 0755);
    }
}

class ConvertPicture extends App
{
    public function execute(): void
    {
        $directory = $this->listenDirectory();

        if (!$directory) {
            $this->showMessage('ディレクトリが正しくありません。');
        }

        $files = $this->getFilesFromDirectory($directory);

        foreach ($files as $file) {
            $this->convertJpg($file);
        }
    }

    /**
     * ディレクトリ名の判定
     *
     * @param void
     * @return string
     */
    public function listenDirectory(): string
    {
        $dir = $this->inputDirectoryName('ディレクトリの名前を相対パスで入力してください');

        if (!is_dir($dir)) {
            return false;
        }

        return $dir;
    }

    /**
     * ディレクトリからファイルを再帰的に取得
     *
     * @param string
     * @return array
     */
    public function getFilesFromDirectory($directory): array
    {
        $files = [];

        $pattern = glob(rtrim($directory, '/') . '/*');

        foreach ($pattern as $file) {
            if (is_file($file)) {
                $jpg = $this->determineJpg($file);
                if ($jpg) {
                    $files[] = $file;
                }
            } else if (is_dir($file)) {
                $files = array_merge($files, $this->getFilesFromDirectory($file));
            }
        }

        return $files;
    }

    /**
     * 取得したパスのファイルが.(jpg|jpeg)形式か判定
     *
     * @param string
     * @return bool
     */
    public function determineJpg($file): bool
    {
        //ファイル形式がJPGか判断
        if (file_exists($file) && exif_imagetype($file) === 2) {
            return true;
        } else {
            return false;
        }
    }

    /**
     * 取得した.(jpg|jpeg)形式のファイルをPNG変換し、各ディレクトリに保存
     *
     * @param string
     * @return
     */
    public function convertJpg($file): void
    {
        $filename = (basename($file));

        $image = @imagecreatefromjpeg($file);

        if (!$image) {
            echo "{$filename}の取得に失敗しました。" . PHP_EOL;
            return;
        }

        echo "{$filename}をPNGに変換しています。" . PHP_EOL;

        $result = $this->convertImgFile($file, $image);

        if ($result) {
            echo "{$filename}の変換がおわりました。" . PHP_EOL;

            imagedestroy($image);

        } else {
            echo "{$filename}の変換ができませんでした。" . PHP_EOL;
        }
    }

    public function convertImgFile(string $file, $image): bool
    {
        $filepath = pathinfo($file);

        $permission = $this->setDirectoryToPermission($filepath['dirname']);

        if (!$permission) {
            return false;
        }

        $result = imagepng($image, $filepath['dirname'] . '/' . $filepath['filename'] . '.png');

        return $result;
    }
}
