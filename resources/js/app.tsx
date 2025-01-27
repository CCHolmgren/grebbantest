import React, { useState, useEffect } from 'react'
import { createRoot } from 'react-dom/client'

interface PuzzleProps {
	rows?: number
	columns?: number
	shuffle?: boolean
}

function Puzzle<PuzzleProps>({ rows = 4, columns = 4, defaultShuffle = false }) {
	const defaultBoard = Array.from({ length: rows }, (v, x) =>
		Array.from({ length: columns }, (vv, y) => {
			let val = x * columns + y + 1

			if (val === rows * columns) {
				val = 0
			}

			return val
		})
	)

	const [board, setBoard] = useState(defaultBoard)

	function shuffle() {
		for (let i in board) {
			for (let j in board[i]) {
				let newI = Math.floor(Math.random() * rows)
				let newJ = Math.floor(Math.random() * columns)

				let temp = board[newI][newJ]
				board[newI][newJ] = board[i][j]
				board[i][j] = temp
			}
		}

		setBoard(board.slice())
	}

	function move(i, j) {
		function _move(i, j, board) {
			let zeroI = -1
			let zeroJ = -1

			for (let ii in board) {
				for (let jj in board[ii]) {
					if (board[ii][jj] === 0) {
						zeroI = Number(ii)
						zeroJ = Number(jj)
						break
					}
				}
			}

			// Missing zero
			if (zeroI === -1 || zeroJ === -1) {
				return false
			}

			let distI = zeroI - i
			let distJ = zeroJ - j

			// Diagonal is disallowed
			if (distI !== 0 && distJ !== 0) {
				return false
			}

			while (distI !== 0 || distJ !== 0) {
				let temp = board[zeroI][zeroJ]

				let stepI = -1 * Math.sign(distI)
				let stepJ = -1 * Math.sign(distJ)

				board[zeroI][zeroJ] = board[zeroI + stepI][zeroJ + stepJ]
				board[zeroI + stepI][zeroJ + stepJ] = temp

				zeroI += stepI
				zeroJ += stepJ

				distI = zeroI - i
				distJ = zeroJ - j
			}

			return board
		}

		let result = _move(i, j, board)

		if (result !== false) {
			setBoard(result.slice())
			requestAnimationFrame(() => requestAnimationFrame(checkDone))
		}
	}

	function checkDone() {
		let done = true

		for (let i in board) {
			for (let j in board[i]) {
				let cell = board[i][j]

				if (cell === 0 && i == rows - 1 && j == columns - 1) {
					continue
				}

				if (board[i][j] !== Number(i) * rows + Number(j) + 1) {
					done = false
					break
				}
			}
		}

		if (done) {
			alert('You won!')
		}
	}

	useEffect(() => {
		if (defaultShuffle) {
			shuffle()
		}
	}, [])

	return (
		<main className="flex w-full min-h-screen justify-center items-center">
			<style>{`
            :root {
                --col-size: calc(${90 / columns}vmin - ((${columns} - 1) * 4px)/${columns});
                --row-size: calc(${90 / rows}vmin - ((${rows} - 1) * 4px)/${rows});
                --font-size: calc(${70 / Math.max(rows, columns)}vmin - ((${Math.max(
				rows,
				columns
			)} - 1) * 4px)/${Math.max(rows, columns)});
            }
            .puzzle-block {
                width: var(--col-size);
                height: var(--row-size);
                font-size: var(--font-size);
                line-height: 1;
            }
            `}</style>
			<div id="blocks" className="flex flex-col gap-1 w-full justify-center items-center">
				{board.map((row, i) => (
					<div key={i} className="flex gap-1">
						{row.map((cell, j) =>
							cell === 0 ? (
								<div
									key={cell}
									className="puzzle-block border border-transparent transparent inline-block size-12"
								></div>
							) : (
								<button
									onClick={() => move(i, j)}
									key={cell}
									className="puzzle-block border border-black inline-block size-12"
								>
									{cell}
								</button>
							)
						)}
					</div>
				))}

				<div className="h-[10vmin] flex">
					<button onClick={shuffle} className="mt-auto">
						<span className="rounded-full bg-black text-white p-4 w-full">Shuffle</span>
					</button>
				</div>
			</div>
		</main>
	)
}

// Render your React component instead
const root = createRoot(document.getElementById('app'))
root.render(<Puzzle rows={4} columns={4} />)
