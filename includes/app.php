<?php

declare(strict_types=1);
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/finance.php';
require_once __DIR__ . '/data.php';
require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/pages.php';
header('Cache-Control: no-store, private');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('Referrer-Policy: same-origin');
requireLogin();
requireSchema();
if (str_ends_with($_SERVER['SCRIPT_NAME'] ?? '', '/dashboard/index.php')) {
    generateReminders(usuarioLogado());
}

function module_page(string $module, string $action = 'index'): void
{
    ob_start();
    $current = $module;
    $cfg = modules()[$module] ?? null;
    $pageTitle =
        $cfg['title'] ??
        [
            'dashboard' => 'Visão geral',
            'relatorios' => 'Relatórios',
            'usuario' => 'Meu perfil',
            'notificacoes' => 'Notificações'
        ][$module];
    $errors = [];
    $item = [];
    $id = (int) ($_GET['id'] ?? ($_POST['id'] ?? ($cfg ? $_POST[$cfg['pk']] ?? 0 : 0)));
    try {
        $post = $_SERVER['REQUEST_METHOD'] === 'POST';
        $onlyPost = in_array($action, [
            'excluir',
            'salvar',
            'confirmar',
            'estornar',
            'estornar_resgate',
            'ler',
            'ler_todas'
        ]);
        if ($onlyPost && !$post) {
            http_response_code(405);
            header('Allow: POST');
            throw new DomainException('Esta ação precisa ser enviada pelo formulário do sistema.');
        }
        if ($post) {
            verifyCsrf();
            verifyNonce();
            transaction(function () use ($module, $action, $id, $cfg) {
                if ($module === 'notificacoes') {
                    if ($action === 'ler_todas') {
                        query(
                            'UPDATE notificacao SET data_leitura=COALESCE(data_leitura,NOW()) WHERE id_usuario=?',
                            [usuarioLogado()]
                        );
                    } elseif ($action === 'ler') {
                        query(
                            'UPDATE notificacao SET data_leitura=COALESCE(data_leitura,NOW()) WHERE id_notificacao=? AND id_usuario=?',
                            [$id, usuarioLogado()]
                        );
                    } else {
                        throw new DomainException('Ação inválida.');
                    }
                    return;
                }
                if ($module === 'usuario') {
                    if (!in_array($action, ['editar', 'alterar_senha'])) {
                        throw new DomainException('Ação inválida.');
                    }
                    saveProfile($action);
                    return;
                }
                if (!$cfg) {
                    throw new DomainException('Ação inválida.');
                }
                switch ($action) {
                    case 'excluir':
                        deleteRecord($module, $id);
                        break;
                    case 'confirmar':
                        confirmRecurring($module, $id);
                        break;
                    case 'quitar':
                        if ($module !== 'cartoes') {
                            throw new DomainException('Ação inválida.');
                        }
                        payCard($id);
                        break;
                    case 'estornar':
                        if ($module !== 'cartoes') {
                            throw new DomainException('Ação inválida.');
                        }
                        undoPayment($id);
                        break;
                    case 'estornar_resgate':
                        if ($module !== 'investimentos') {
                            throw new DomainException('Ação inválida.');
                        }
                        undoRedemption($id);
                        break;
                    case 'resgatar':
                        if ($module !== 'investimentos') {
                            throw new DomainException('Ação inválida.');
                        }
                        redeem($id);
                        break;
                    case 'editar':
                        if ($id < 1) {
                            throw new DomainException('Registro inválido.');
                        }
                        saveRecord($module, $id);
                        break;
                    case 'cadastrar':
                        saveRecord($module);
                        break;
                    case 'salvar':
                        saveRecord($module, $id);
                        break;
                    default:
                        throw new DomainException('Ação inválida.');
                }
            });
            consumeNonce();
            flash(
                'success',
                match ($action) {
                    'excluir'
                        => 'Registro excluído e efeitos financeiros estornados quando aplicável.',
                    'confirmar' => 'Ocorrência confirmada. A próxima data foi atualizada.',
                    'quitar' => 'Cartão quitado. Saldo da conta e limite atualizados.',
                    'resgatar' => 'Resgate concluído. Valor creditado na conta de origem.',
                    'estornar' => 'Quitação estornada. O saldo e o limite foram atualizados.',
                    default => 'Alterações salvas com sucesso.'
                }
            );
            redirect(url($module . '/' . ($module === 'usuario' ? 'perfil.php' : 'index.php')));
        }
        if ($cfg && $action === 'editar') {
            $item =
                $module === 'categorias'
                    ? one(
                        'SELECT c.* FROM categoria c JOIN sc_categoria_usuario o ON o.id_categoria=c.id_categoria WHERE c.id_categoria=? AND o.id_usuario=?',
                        [$id, usuarioLogado()]
                    )
                    : owned($cfg['table'], $id, false);
            if (!$item) {
                throw new DomainException('Registro não encontrado ou sem permissão.');
            }
        }
    } catch (Throwable $ex) {
        $errors[] = safeError($ex);
        if (
            $_SERVER['REQUEST_METHOD'] === 'POST' &&
            !in_array($action, ['cadastrar', 'editar', 'quitar', 'resgatar', 'alterar_senha'])
        ) {
            flash('error', $errors[0]);
            redirect(url($module . '/' . ($module === 'usuario' ? 'perfil.php' : 'index.php')));
        }
    }
    // Downloads precisam ser processados antes do HTML e da paginação da tela.
    if ($module === 'relatorios' && in_array($_GET['exportar'] ?? '', ['csv', 'pdf'], true)) {
        try {
            [$i, $f] = period();
            $kind = in_array($action, [...array_keys(reportKinds()), 'completo'], true)
                ? $action
                : 'despesas';
            if ($kind === 'completo' && $_GET['exportar'] !== 'pdf') {
                throw new DomainException(
                    'Use PDF para exportar as quatro áreas juntas. O CSV continua disponível nos relatórios individuais.'
                );
            }
            $data = $kind === 'completo' ? reportSections($i, $f) : reportData($kind, $i, $f);
            if ($_GET['exportar'] === 'pdf') {
                require_once __DIR__ . '/report_pdf.php';
                exportPDF($data, $kind, $i, $f);
            }
            exportCSV($data, $kind);
        } catch (Throwable $ex) {
            http_response_code($ex instanceof DomainException ? 422 : 500);
            $errors[] = safeError($ex);
        }
    }
    require __DIR__ . '/header.php';
    try {
        if ($onlyPost ?? false) {
            foreach ($errors as $er) {
                echo '<div class="alert error" role="alert">' . e($er) . '</div>';
            }
        } elseif ($module === 'dashboard') {
            dashboard();
        } elseif ($module === 'notificacoes') {
            notifications();
        } elseif ($module === 'relatorios') {
            if ($errors) {
                foreach ($errors as $er) {
                    echo '<div class="alert error" role="alert">' . e($er) . '</div>';
                }
                echo linkButton(
                    url('relatorios/index.php'),
                    'Voltar aos relatórios',
                    'btn-secondary'
                );
            } else {
                reports(
                    in_array($action, [...array_keys(reportKinds()), 'completo'], true)
                        ? $action
                        : 'despesas'
                );
            }
        } elseif ($module === 'usuario') {
            profile(in_array($action, ['editar', 'alterar_senha']) ? $action : 'perfil', $errors);
        } elseif ($cfg) {
            if ($action === 'cadastrar' || $action === 'editar') {
                if ($action === 'editar' && !$item && !$post) {
                    throw new DomainException($errors[0] ?? 'Registro não encontrado.');
                }
                recordForm($module, $action === 'editar' ? $id : 0, $item, $errors);
            } elseif (in_array($action, ['quitar', 'resgatar'])) {
                operationForm($module, $action, $id, $errors);
            } else {
                recordList($module);
            }
        }
    } catch (Throwable $ex) {
        http_response_code($ex instanceof DomainException ? 422 : 500);
        echo '<div class="alert error" role="alert">' .
            e(safeError($ex)) .
            '</div>' .
            linkButton(url($module . '/index.php'), 'Voltar', 'btn-secondary');
    }
    require __DIR__ . '/footer.php';
    ob_end_flush();
}
