<template>
  <Layout ref="load" class="bonus-detail bg-f8">
    <Navbar :title="navbarTitle" />
    <van-cell-group class="cell-group card-group-box">
      <van-cell class="cell">
        <div class="white">{{$store.state.member.bonusSetText.common.withdrawals_bonus}}</div>
        <van-row type="flex" justify="space-between" class="box">
          <span class="text letter-price">{{detail.grant_bonus | yuan}}</span>
          <van-button
            size="small"
            plain
            type="danger"
            @click="$router.push('/bonus/log')"
          >{{$store.state.member.bonusSetText.common.bonus_details}}</van-button>
        </van-row>
        <van-row type="flex" justify="center" class="box-foot">
          <div class="item white">
            <div>{{$store.state.member.bonusSetText.common.withdrawal_bonus}}</div>
            <div class="letter-price">{{detail.ungrant_bonus | yuan}}</div>
            <div class="fs-12 text">{{$store.state.member.bonusSetText.common.withdrawal_bonus}}</div>
          </div>
          <div class="item white">
            <div>{{$store.state.member.bonusSetText.common.frozen_bonus}}</div>
            <div class="letter-price">{{detail.freezing_bonus | yuan}}</div>
            <div class="fs-12 text">{{$store.state.member.bonusSetText.common.frozen_bonus}}</div>
          </div>
        </van-row>
      </van-cell>
    </van-cell-group>
    <van-tabs class="cell-group card-group-box">
      <van-tab :title="item.name" v-for="(item,index) in tabs" :key="index">
        <van-cell :border="false">
          <div class="box-head">
            <div class="text-center">{{item.text_grant_bonus}}</div>
            <span class="text letter-price">{{(item.grant_bonus) | yuan}}</span>
          </div>
          <van-row type="flex" justify="center" class="box-foot">
            <div class="item">
              <div>{{item.text_ungrant_bonus}}</div>
              <div class="text-maintone letter-price">{{item.ungrant_bonus | yuan}}</div>
              <div class="fs-12 text-regular">{{item.text_ungrant_bonus}}</div>
            </div>
            <div class="item">
              <div>{{item.text_freezing_bonus}}</div>
              <div class="text-maintone letter-price">{{item.freezing_bonus | yuan}}</div>
              <div class="fs-12 text-regular">{{item.text_freezing_bonus}}</div>
            </div>
          </van-row>
        </van-cell>
      </van-tab>
    </van-tabs>
  </Layout>
</template>

<script>
import sfc from "@/utils/create";
import { isEmpty } from "@/utils/util";
import { GET_BONUSDETAIL } from "@/api/bonus";
export default sfc({
  name: "bonus-detail",
  data() {
    return {
      detail: {}
    };
  },
  computed: {
    navbarTitle() {
      const { bonus_money } = this.$store.state.member.bonusSetText.common;
      let title = bonus_money;
      document.title = title;
      return title;
    },
    tabs() {
      const detail = this.detail;
      const {
        globalbonus,
        areabonus,
        teambonus
      } = this.$store.state.config.addons;
      const {
        is_global_agent,
        is_area_agent,
        is_team_agent
      } = this.$store.state.member.info;
      const { area, global, team } = this.$store.state.member.bonusSetText;
      let arr = [];
      if (!isEmpty(detail)) {
        if (globalbonus && is_global_agent == 2) {
          arr.push({
            name: global.global_agreement,
            grant_bonus: detail.global.grant_bonus,
            ungrant_bonus: detail.global.ungrant_bonus,
            freezing_bonus: detail.global.freezing_bonus,
            text_grant_bonus: global.withdrawals_global_bonus,
            text_ungrant_bonus: global.withdrawal_global_bonus,
            text_freezing_bonus: global.frozen_global_bonus
          });
        }
        if (areabonus && is_area_agent == 2) {
          arr.push({
            name: area.area_agreement,
            grant_bonus: detail.area.grant_bonus,
            ungrant_bonus: detail.area.ungrant_bonus,
            freezing_bonus: detail.area.freezing_bonus,
            text_grant_bonus: area.withdrawals_area_bonus,
            text_ungrant_bonus: area.withdrawal_area_bonus,
            text_freezing_bonus: area.frozen_area_bonus
          });
        }
        if (teambonus && is_team_agent == 2) {
          arr.push({
            name: team.team_agreement,
            grant_bonus: detail.team.grant_bonus,
            ungrant_bonus: detail.team.ungrant_bonus,
            freezing_bonus: detail.team.freezing_bonus,
            text_grant_bonus: team.withdrawals_team_bonus,
            text_ungrant_bonus: team.withdrawal_team_bonus,
            text_freezing_bonus: team.frozen_team_bonus
          });
        }
      }
      return arr;
    }
  },
  activated() {
    const $this = this;
    if (this.navbarTitle) {
      document.title = this.navbarTitle;
    }
    GET_BONUSDETAIL()
      .then(({ data }) => {
        $this.detail = data;
        $this.$refs.load.success();
      })
      .catch(() => {
        $this.$refs.load.fail();
      });
  }
});
</script>

<style scoped>
.cell {
  padding: 20px 15px;
  background: #ff454e;
  color: #fff;
  border-top-right-radius: 5px;
  border-top-left-radius: 5px;
}

.cell-group .box {
  align-items: center;
  padding: 10px 0;
  border-bottom: 1px solid #eee;
}

.cell-group .box .text {
  color: #ffffff;
  font-size: 20px;
}

.cell-group .text >>> .van-cell__value {
  color: #ff454e;
  font-size: 20px;
  line-height: 38px;
}

.box-foot .item {
  flex: 1;
  text-align: center;
  padding: 10px 0;
  border-right: 1px solid #eee;
}

.box-foot .item:last-child {
  border-right: 0;
}

.box-head {
  padding: 10px 0;
  border-bottom: 1px solid #eee;
  text-align: center;
}

.box-head .text {
  color: #ff454e;
  font-size: 20px;
  line-height: 38px;
}

.white {
  color: #fff;
}
</style>
