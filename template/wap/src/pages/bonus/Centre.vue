<template>
  <Layout ref="load" class="bonus-centre bg-f8">
    <Navbar :title="navbarTitle" />
    <Header :info="info" :agent-info="agentInfo" />
    <CellPanelGroup :show-head="false" :items="cellPanelItems" v-if="isBonus" />
    <CellCardGroup :show-head="false" :items="cellCardItems" @click="toLink" v-if="isBonus" />
    <CellCardGroup
      :show-head="false"
      :items="cellApplyItems"
      @click="toLink"
      v-if="cellApplyItems.length>0"
    />
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import CellPanelGroup from "@/components/CellPanelGroup";
import CellCardGroup from "@/components/CellCardGroup";
import Header from "./component/Header";
import { GET_CENTREINFO } from "@/api/bonus";
import { bindMobile } from "@/mixins";
import { _encode } from "@/utils/base64";
export default sfc({
  name: "bonus-centre",
  data() {
    return {
      info: {}
    };
  },
  mixins: [bindMobile],
  computed: {
    navbarTitle() {
      const { bonus_name } = this.$store.state.member.bonusSetText.common;
      let title = bonus_name;
      document.title = title;
      return title;
    },
    isBonus() {
      const info = this.info;
      let flag = false;
      if (
        (info.global_is_start == 1 && info.is_global_agent == 2) ||
        (info.area_is_start == 1 && info.is_area_agent == 2) ||
        (info.team_is_start == 1 && info.is_team_agent == 2)
      ) {
        flag = true;
      }
      return flag;
    },
    agentInfo() {
      const info = this.info;
      const { area, global, team } = this.$store.state.member.bonusSetText;
      const arr = [];
      if (info.global_is_start == 1) {
        let global_obj = {};
        global_obj.name = global.global_agreement;
        global_obj.applyLink = "/bonus/apply/1";
        global_obj.levelLink = "/bonus/level/3";
        global_obj.applyState = info.is_global_agent;
        global_obj.level_name = info.global_level_name;
        arr.push(global_obj);
      }
      if (info.area_is_start == 1) {
        let area_obj = {};
        area_obj.name = area.area_agreement;
        area_obj.applyLink = "/bonus/apply/2";
        area_obj.levelLink = "/bonus/level/2";
        area_obj.applyState = info.is_area_agent;
        area_obj.level_name = info.area_level_name;
        arr.push(area_obj);
      }
      if (info.team_is_start == 1) {
        let team_obj = {};
        team_obj.name = team.team_agreement;
        team_obj.applyLink = "/bonus/apply/3";
        team_obj.levelLink = "/bonus/level/1";
        team_obj.applyState = info.is_team_agent;
        team_obj.level_name = info.team_level_name;
        arr.push(team_obj);
      }
      return arr;
    },
    cellPanelItems() {
      const info = this.info;
      const {
        withdrawals_bonus,
        withdrawal_bonus,
        frozen_bonus
      } = this.$store.state.member.bonusSetText.common;
      return [
        {
          title: withdrawals_bonus,
          text: info.grant_bonus ? info.grant_bonus : 0
        },
        {
          title: withdrawal_bonus,
          text: info.ungrant_bonus ? info.ungrant_bonus : 0
        },
        {
          title: frozen_bonus,
          text: info.freezing_bonus ? info.freezing_bonus : 0
        }
      ];
    },
    cellCardItems() {
      const info = this.info;
      const {
        bonus_money,
        bonus_order
      } = this.$store.state.member.bonusSetText.common;
      let items = [
        {
          text: bonus_money,
          icon: "v-icon-balance2",
          path: "/bonus/detail"
        },
        {
          text: bonus_order,
          icon: "v-icon-text1",
          path: "/bonus/order"
        }
      ];
      if (this.$store.state.config.addons.credential) {
        const roleType = [];
        if (info.is_team_agent == 2) {
          roleType.push(1);
        }
        if (info.is_area_agent == 2) {
          roleType.push(2);
        }
        if (info.is_global_agent == 2) {
          roleType.push(3);
        }
        let enCodeRoleType = _encode(JSON.stringify(roleType));
        let rolePath = "/bonus/certificate?roleType=" + enCodeRoleType;
        items.push({
          text: "授权证书",
          icon: "v-icon-guarantee",
          path: rolePath
        });
      }
      return items;
    },
    cellApplyItems() {
      const info = this.info;
      const {
        global,
        area,
        team,
        common
      } = this.$store.state.member.bonusSetText;
      const arr = [];
      if (
        info.global_is_start == 1 &&
        (info.complete_datum_global || info.is_global_agent != 2)
      ) {
        let global_obj = {};
        let text =
          info.is_global_agent == 0 ? global.apply_global : "查看申请情况";
        global_obj.text = info.complete_datum_global
          ? "完善" + global.global_agreement + "资料"
          : text;
        global_obj.path = info.complete_datum_global
          ? "/bonus/apply/1#replenish"
          : "/bonus/apply/1";
        global_obj.state = info.is_global_agent;
        global_obj.icon = "v-icon-shareholder-agent";
        arr.push(global_obj);
      }
      if (info.area_is_start == 1 && info.is_area_agent != 2) {
        let area_obj = {};
        let text = info.is_area_agent == 0 ? area.apply_area : "查看申请情况";
        area_obj.text = text;
        area_obj.path = "/bonus/apply/2";
        area_obj.icon = "v-icon-region-agent";
        arr.push(area_obj);
      }
      if (
        info.team_is_start == 1 &&
        (info.complete_datum_team || info.is_team_agent != 2)
      ) {
        let team_obj = {};
        let text = info.is_team_agent == 0 ? team.apply_team : "查看申请情况";
        team_obj.text = info.complete_datum_team
          ? "完善" + team.team_agreement + "资料"
          : text;
        team_obj.path = info.complete_datum_team
          ? "/bonus/apply/3#replenish"
          : "/bonus/apply/3";
        team_obj.icon = "v-icon-team-agent";
        arr.push(team_obj);
      }
      return arr;
    }
  },
  activated() {
    const $this = this;
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    // 是否分销商
    $this.$store
      .dispatch("isBistributor")
      .then(() => {
        $this.loadData();
      })
      .catch(({ error, callback }) => {
        if (error) {
          this.$refs.load.fail();
        }
        if (callback) {
          this.$refs.load.result();
          this.$Toast(
            "请先成为" +
              this.$store.state.member.commissionSetText.distributor_name +
              "！"
          );
          this.$router.replace("/member/centre");
        }
      });
  },
  methods: {
    loadData() {
      const $this = this;
      GET_CENTREINFO()
        .then(({ data }) => {
          $this.info = data;
          $this.$refs.load.success();
        })
        .catch(() => {
          $this.$refs.load.fail();
        });
    },
    toLink({ path }) {
      this.bindMobile().then(() => {
        this.$router.push(path);
      });
    }
  },
  components: {
    CellPanelGroup,
    CellCardGroup,
    Header
  }
});
</script>

<style scoped>
</style>
