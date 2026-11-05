# Third-party skills

Copied into this repo, not installed as plugins, so updates are manual.

## mattpocock/skills

- Source: https://github.com/mattpocock/skills
- Commit: 4ae2317ddb22b046e26455e4f9c9b3aaaf62212d
- License: MIT, see `licenses/mattpocock-skills.LICENSE`
- Included: the 27 skills listed in upstream `.claude-plugin/plugin.json`
- Changes: `code-review` is renamed to `matt-code-review` so it does not shadow the Claude Code built-in `/code-review`, and references in `ask-matt`, `implement`, `implement-spec` and `tdd` are updated. `agents/openai.yaml` files are omitted because only Codex reads them.

## NVIDIA/SkillSpector

- Source: https://github.com/NVIDIA/SkillSpector
- Commit: 6d0b7c849949cab9181e57798247f89e48ce5d6a
- License: Apache-2.0, see `licenses/skillspector.LICENSE`
- Included: `skill-inspector`, unchanged
- Requires the `skillspector` CLI for static scans (Python 3.12+). Without it, the skill falls back to manual review.
